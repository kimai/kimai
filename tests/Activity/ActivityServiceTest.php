<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Activity;

use App\Activity\ActivityService;
use App\Entity\Activity;
use App\Entity\Project;
use App\Event\ActivityCreateEvent;
use App\Event\ActivityCreatePostEvent;
use App\Event\ActivityCreatePreEvent;
use App\Event\ActivityMetaDefinitionEvent;
use App\Event\ActivityUpdatePostEvent;
use App\Event\ActivityUpdatePreEvent;
use App\Repository\ActivityRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(ActivityService::class)]
class ActivityServiceTest extends TestCase
{
    private function getSut(
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?ActivityRepository $repository = null,
        array $configuration = []
    ): ActivityService {
        if ($repository === null) {
            $repository = $this->createMock(ActivityRepository::class);
        }

        if ($dispatcher === null) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        if ($validator === null) {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->method('validate')->willReturn(new ConstraintViolationList());
        }

        $configuration = SystemConfigurationFactory::createStub(['activity' => $configuration]);

        $service = new ActivityService($repository, $configuration, $dispatcher, $validator);

        return $service;
    }

    public function testsaveNewActivityHasValidationError(): void
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveActivity(new Activity());
    }

    public function testUpdateDispatchesEvents(): void
    {
        $project = $this->createMock(Activity::class);
        $project->method('getId')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) use ($project) {
            if ($event instanceof ActivityUpdatePostEvent) {
                self::assertSame($project, $event->getActivity());
            } elseif ($event instanceof ActivityUpdatePreEvent) {
                self::assertSame($project, $event->getActivity());
            } else {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $sut->saveActivity($project);
    }

    public function testcreateNewActivityDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if (!$event instanceof ActivityMetaDefinitionEvent && !$event instanceof ActivityCreateEvent) {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $project = new Project();
        $activity = $sut->createNewActivity($project);

        self::assertSame($project, $activity->getProject());
    }

    public function testsaveNewActivityDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if (!$event instanceof ActivityCreatePreEvent && !$event instanceof ActivityCreatePostEvent) {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $activity = new Activity();
        $sut->saveActivity($activity);
    }

    public function testcreateNewActivityWithoutCustomer(): void
    {
        $sut = $this->getSut();

        $project = $sut->createNewActivity();
        self::assertNull($project->getProject());

        $project = $sut->createNewActivity();
        self::assertNull($project->getProject());
    }

    #[DataProvider('getTestData')]
    public function testActivityNumber(string $format, int|string $expected): void
    {
        $sut = $this->getSut(null, null, null, ['number_format' => $format]);
        $activity = $sut->createNewActivity();

        self::assertEquals((string) $expected, $activity->getNumber());
    }

    /**
     * @return array<int, array<int, string|\DateTime|int>>
     */
    public static function getTestData(): array
    {
        $dateTime = new \DateTime();

        $yearLong = (int) $dateTime->format('Y');
        $yearShort = (int) $dateTime->format('y');
        $monthLong = $dateTime->format('m');
        $monthShort = (int) $dateTime->format('n');
        $dayLong = $dateTime->format('d');
        $dayShort = (int) $dateTime->format('j');

        return [
            // simple tests for single calls
            ['{ac,1}', '2'],
            ['{ac,2}', '02'],
            ['{ac,3}', '002'],
            ['{ac,4}', '0002'],
            ['{Y}', $yearLong],
            ['{y}', $yearShort],
            ['{M}', $monthLong],
            ['{m}', $monthShort],
            ['{D}', $dayLong],
            ['{d}', $dayShort],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{Y,6}', '00' . $yearLong],
            ['{M,3}', '0' . $monthLong],
            ['{D,3}', '0' . $dayLong],
            // increment dates
            ['{YY}', $yearLong + 1],
            ['{YY+1}', $yearLong + 1],
            ['{YY+2}', $yearLong + 2],
            ['{YY+3}', $yearLong + 3],
            ['{YY-1}', $yearLong - 1],
            ['{YY-2}', $yearLong - 2],
            ['{YY-3}', $yearLong - 3],
            ['{yy}', $yearShort + 1],
            ['{yy+1}', $yearShort + 1],
            ['{yy+2}', $yearShort + 2],
            ['{yy+3}', $yearShort + 3],
            ['{yy-1}', $yearShort - 1],
            ['{yy-2}', $yearShort - 2],
            ['{yy-3}', $yearShort - 3],
            ['{MM}', $monthShort + 1], // cast to int removes leading zero
            ['{MM+1}', $monthShort + 1], // cast to int removes leading zero
            ['{MM+2}', $monthShort + 2], // cast to int removes leading zero
            ['{MM+3}', $monthShort + 3], // cast to int removes leading zero
            ['{DD}', $dayShort + 1], // cast to int removes leading zero
            ['{DD+1}', $dayShort + 1], // cast to int removes leading zero
            ['{DD+2}', $dayShort + 2], // cast to int removes leading zero
            ['{DD+3}', $dayShort + 3], // cast to int removes leading zero
        ];
    }
}
