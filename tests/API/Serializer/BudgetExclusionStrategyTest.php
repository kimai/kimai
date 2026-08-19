<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Serializer;

use App\API\Serializer\BudgetExclusionStrategy;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(BudgetExclusionStrategy::class)]
class BudgetExclusionStrategyTest extends TestCase
{
    private const BUDGET_FIELDS = ['budget', 'timeBudget', 'budgetType'];

    /**
     * @return SerializationContext&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContext(?object $object)
    {
        $context = $this->createMock(SerializationContext::class);
        $context->method('getObject')->willReturn($object);

        return $context;
    }

    public function testShouldNeverSkipAClass(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new BudgetExclusionStrategy($security);

        self::assertFalse($sut->shouldSkipClass(new ClassMetadata(Project::class), $this->createContext(new Project())));
    }

    public function testShouldNeverSkipPropertiesOfOtherClasses(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new BudgetExclusionStrategy($security);
        $context = $this->createContext(new Timesheet());

        // "rate" is not a budget field and Timesheet does not use the BudgetTrait
        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Timesheet::class, 'rate'), $context));
    }

    public function testShouldNeverSkipOtherProperties(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new BudgetExclusionStrategy($security);

        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Activity::class, 'name'), $this->createContext(new Activity())));
        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Customer::class, 'name'), $this->createContext(new Customer('testing'))));
        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Project::class, 'name'), $this->createContext(new Project())));
    }

    public function testSkipsBudgetFieldsWithoutPermission(): void
    {
        $project = new Project();

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        // one voter call per permission, cached per record: "budget" for the budget
        // field, "time" for the time budget and both for the shared budget type
        $security->expects(self::exactly(2))->method('isGranted')->willReturn(false);

        $sut = new BudgetExclusionStrategy($security);
        $context = $this->createContext($project);

        foreach (self::BUDGET_FIELDS as $field) {
            self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Project::class, $field), $context));
        }
    }

    public function testSerializesBudgetFieldsWithPermission(): void
    {
        $customer = new Customer('testing');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::exactly(2))->method('isGranted')->willReturnCallback(
            function (string $permission, Customer $subject) use ($customer): bool {
                self::assertSame($customer, $subject);
                self::assertContains($permission, ['budget', 'time']);

                return true;
            }
        );

        $sut = new BudgetExclusionStrategy($security);
        $context = $this->createContext($customer);

        foreach (self::BUDGET_FIELDS as $field) {
            self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Customer::class, $field), $context));
        }
    }

    public function testBudgetTypeIsSerializedWithOnlyOnePermission(): void
    {
        $activity = new Activity();

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->method('isGranted')->willReturnCallback(
            function (string $permission): bool {
                // "time" is granted, "budget" is not
                return $permission === 'time';
            }
        );

        $sut = new BudgetExclusionStrategy($security);
        $context = $this->createContext($activity);

        self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Activity::class, 'budget'), $context));
        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Activity::class, 'timeBudget'), $context));
        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Activity::class, 'budgetType'), $context));
    }

    public function testDecidesPerRecord(): void
    {
        $mine = new Project();
        $foreign = new Project();

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->method('isGranted')->willReturnCallback(
            function (string $permission, Project $project) use ($mine): bool {
                return $project === $mine;
            }
        );

        $sut = new BudgetExclusionStrategy($security);
        $property = new PropertyMetadata(Project::class, 'budget');

        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($mine)));
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext($foreign)));
    }

    public function testSkipsBudgetFieldsWithoutRecordToCheckPermissionsOn(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new BudgetExclusionStrategy($security);

        // no object is being visited
        self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Project::class, 'budget'), $this->createContext(null)));

        // not a serialization context
        $deserialization = $this->createMock(DeserializationContext::class);
        self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Project::class, 'budget'), $deserialization));
    }
}
