<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Serializer;

use App\API\Serializer\RateExclusionStrategy;
use App\Entity\Activity;
use App\Entity\Timesheet;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(RateExclusionStrategy::class)]
class RateExclusionStrategyTest extends TestCase
{
    private const RATE_FIELDS = ['rate', 'internalRate', 'fixedRate', 'hourlyRate'];

    /**
     * @return SerializationContext&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContext(?Timesheet $timesheet)
    {
        $context = $this->createMock(SerializationContext::class);
        $context->method('getObject')->willReturn($timesheet);

        return $context;
    }

    public function testShouldNeverSkipAClass(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new RateExclusionStrategy($security);

        self::assertFalse($sut->shouldSkipClass(new ClassMetadata(Timesheet::class), $this->createContext(new Timesheet())));
    }

    public function testShouldNeverSkipPropertiesOfOtherClasses(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new RateExclusionStrategy($security);
        $context = $this->createContext(new Timesheet());

        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Activity::class, 'name'), $context));
    }

    public function testShouldNeverSkipOtherTimesheetProperties(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new RateExclusionStrategy($security);
        $context = $this->createContext(new Timesheet());

        self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Timesheet::class, 'description'), $context));
    }

    public function testSkipsRateFieldsWithoutPermission(): void
    {
        $timesheet = new Timesheet();

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        // the decision is cached per record, no matter how many rate fields are serialized
        $security->expects(self::once())->method('isGranted')->with('view_rate', $timesheet)->willReturn(false);

        $sut = new RateExclusionStrategy($security);
        $context = $this->createContext($timesheet);

        foreach (self::RATE_FIELDS as $field) {
            self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Timesheet::class, $field), $context));
        }
    }

    public function testSerializesRateFieldsWithPermission(): void
    {
        $timesheet = new Timesheet();

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::once())->method('isGranted')->with('view_rate', $timesheet)->willReturn(true);

        $sut = new RateExclusionStrategy($security);
        $context = $this->createContext($timesheet);

        foreach (self::RATE_FIELDS as $field) {
            self::assertFalse($sut->shouldSkipProperty(new PropertyMetadata(Timesheet::class, $field), $context));
        }
    }

    public function testDecidesPerRecord(): void
    {
        $mine = new Timesheet();
        $foreign = new Timesheet();

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::exactly(2))->method('isGranted')->willReturnCallback(
            function (string $attribute, Timesheet $timesheet) use ($mine): bool {
                self::assertEquals('view_rate', $attribute);

                return $timesheet === $mine;
            }
        );

        $sut = new RateExclusionStrategy($security);
        $property = new PropertyMetadata(Timesheet::class, 'rate');

        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($mine)));
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext($foreign)));
        // cached decisions
        self::assertFalse($sut->shouldSkipProperty($property, $this->createContext($mine)));
        self::assertTrue($sut->shouldSkipProperty($property, $this->createContext($foreign)));
    }

    public function testSkipsRateFieldsWithoutRecordToCheckPermissionsOn(): void
    {
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects(self::never())->method('isGranted');

        $sut = new RateExclusionStrategy($security);

        // no object is being visited
        self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Timesheet::class, 'rate'), $this->createContext(null)));

        // not a serialization context
        $deserialization = $this->createMock(DeserializationContext::class);
        self::assertTrue($sut->shouldSkipProperty(new PropertyMetadata(Timesheet::class, 'rate'), $deserialization));
    }
}
