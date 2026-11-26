<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Serializer;

use App\API\Serializer\UserEntityExclusionStrategy;
use App\Entity\Team;
use App\Entity\User;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserEntityExclusionStrategy::class)]
class UserEntityExclusionStrategyTest extends TestCase
{
    private const ENTITY_FIELDS = ['visiblePreferences', 'roles', 'supervisor', 'memberships', 'teams'];

    /**
     * @return SerializationContext&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContext(?object ...$visiting)
    {
        $stack = new \SplStack();
        foreach ($visiting as $object) {
            if ($object !== null) {
                $stack->push($object);
            }
        }

        $context = $this->createMock(SerializationContext::class);
        $context->method('getObject')->willReturn($stack->isEmpty() ? null : $stack->top());
        $context->method('getVisitingStack')->willReturn($stack);

        return $context;
    }

    /**
     * @param array<string>|null $groups
     */
    private function createProperty(string $class, string $name, ?array $groups): PropertyMetadata
    {
        $property = new PropertyMetadata($class, $name);
        $property->groups = $groups;

        return $property;
    }

    public function testShouldNeverSkipAClass(): void
    {
        $sut = new UserEntityExclusionStrategy([]);

        self::assertFalse($sut->shouldSkipClass(new ClassMetadata(User::class), $this->createContext(new User())));
    }

    public function testShouldNeverSkipPropertiesOfOtherClasses(): void
    {
        $sut = new UserEntityExclusionStrategy([]);
        $context = $this->createContext(new Team('foo'));

        self::assertFalse($sut->shouldSkipProperty($this->createProperty(Team::class, 'members', ['User_Entity']), $context));
    }

    public function testShouldNeverSkipBasicUserProperties(): void
    {
        $sut = new UserEntityExclusionStrategy([]);
        $context = $this->createContext(new User());

        self::assertFalse($sut->shouldSkipProperty($this->createProperty(User::class, 'username', ['Default']), $context));
        self::assertFalse($sut->shouldSkipProperty($this->createProperty(User::class, 'username', null), $context));
        self::assertFalse($sut->shouldSkipProperty($this->createProperty(User::class, 'username', ['Default', 'User_Entity']), $context));
    }

    public function testSerializesEntityFieldsOfAllowedUsers(): void
    {
        $user = new User();

        $sut = new UserEntityExclusionStrategy([$user]);
        $context = $this->createContext($user);

        foreach (self::ENTITY_FIELDS as $field) {
            self::assertFalse($sut->shouldSkipProperty($this->createProperty(User::class, $field, ['User_Entity']), $context));
        }
    }

    public function testSkipsEntityFieldsOfOtherUsers(): void
    {
        $user = new User();
        $supervisor = new User();
        $user->setSupervisor($supervisor);

        $sut = new UserEntityExclusionStrategy([$user]);
        $context = $this->createContext($supervisor);

        foreach (self::ENTITY_FIELDS as $field) {
            self::assertTrue($sut->shouldSkipProperty($this->createProperty(User::class, $field, ['User_Entity']), $context));
        }
    }

    public function testSkipsEntityFieldsOfAllowedUsersWhenEmbedded(): void
    {
        $user = new User();
        $supervisor = new User();
        $user->setSupervisor($supervisor);

        // both are allowed, e.g. in a collection which contains the user and its supervisor
        $sut = new UserEntityExclusionStrategy([$user, $supervisor]);
        $property = $this->createProperty(User::class, 'visiblePreferences', ['User_Entity']);

        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($user)));
        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($supervisor)));
        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext(new Team('foo'), $user)));
        // embedded supervisor
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext($user, $supervisor)));
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext($user, new Team('foo'), $supervisor)));
    }

    public function testDecidesPerUser(): void
    {
        $first = new User();
        $second = new User();
        $other = new User();

        $sut = new UserEntityExclusionStrategy(new \ArrayIterator([$first, $second]));
        $property = $this->createProperty(User::class, 'visiblePreferences', ['User_Entity']);

        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($first)));
        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($second)));
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext($other)));
    }

    public function testSkipsEntityFieldsWithoutUserToCheck(): void
    {
        $sut = new UserEntityExclusionStrategy([new User()]);
        $property = $this->createProperty(User::class, 'visiblePreferences', ['User_Entity']);

        // no object is being visited
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext()));

        // not a serialization context
        $deserialization = $this->createMock(DeserializationContext::class);
        self::assertTrue($sut->shouldSkipProperty($property, $deserialization));
    }
}
