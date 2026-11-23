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

    /**
     * @param \Closure(\DateTimeInterface): string $expected
     */
    #[DataProvider('getTestData')]
    public function testActivityNumber(string $format, \Closure $expected): void
    {
        $sut = $this->getSut(null, null, null, ['number_format' => $format]);

        $date = new \DateTimeImmutable();
        $activity = $sut->createNewActivity();

        self::assertEquals($expected($date), $activity->getNumber());
    }

    public function testActivityNumberIncrementsForMultipleCreateCallsOnSameInstance(): void
    {
        $sut = $this->getSut(null, null, null, ['number_format' => '{ac,1}']);

        $activity1 = $sut->createNewActivity();
        $activity2 = $sut->createNewActivity();
        $activity3 = $sut->createNewActivity();

        // countActivity() is mocked and returns 0, the formatter normalizes increaseBy=0 to 1,
        // so the first generated number is 2. The in-instance counter must bump subsequent calls.
        self::assertEquals('2', $activity1->getNumber());
        self::assertEquals('3', $activity2->getNumber());
        self::assertEquals('4', $activity3->getNumber());
    }

    /**
     * @return array<int, array{0: string, 1: \Closure(\DateTimeInterface): string}>
     */
    public static function getTestData(): array
    {
        $literal = static fn (string $value): \Closure => static fn (): string => $value;
        $date = static fn (string $format): \Closure => static fn (\DateTimeInterface $d): string => $d->format($format);
        $yearLong = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('Y') + $add);
        $yearShort = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('y') + $add);
        $monthShort = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('m') + $add);
        $dayShort = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('d') + $add);

        return [
            // simple tests for single calls
            ['{ac,1}', $literal('2')],
            ['{ac,2}', $literal('02')],
            ['{ac,3}', $literal('002')],
            ['{ac,4}', $literal('0002')],
            ['{Y}', $date('Y')],
            ['{y}', $date('y')],
            ['{M}', $date('m')],
            ['{m}', $date('n')],
            ['{D}', $date('d')],
            ['{d}', $date('j')],
            // number formatting
            ['{Y,6}', static fn (\DateTimeInterface $d): string => '00' . $d->format('Y')],
            ['{M,3}', static fn (\DateTimeInterface $d): string => '0' . $d->format('m')],
            ['{D,3}', static fn (\DateTimeInterface $d): string => '0' . $d->format('d')],
            // increment dates
            ['{YY}', $yearLong(1)],
            ['{YY+1}', $yearLong(1)],
            ['{YY+2}', $yearLong(2)],
            ['{YY+3}', $yearLong(3)],
            ['{YY-1}', $yearLong(-1)],
            ['{YY-2}', $yearLong(-2)],
            ['{YY-3}', $yearLong(-3)],
            ['{yy}', $yearShort(1)],
            ['{yy+1}', $yearShort(1)],
            ['{yy+2}', $yearShort(2)],
            ['{yy+3}', $yearShort(3)],
            ['{yy-1}', $yearShort(-1)],
            ['{yy-2}', $yearShort(-2)],
            ['{yy-3}', $yearShort(-3)],
            ['{MM}', $monthShort(1)], // cast to int removes leading zero
            ['{MM+1}', $monthShort(1)], // cast to int removes leading zero
            ['{MM+2}', $monthShort(2)], // cast to int removes leading zero
            ['{MM+3}', $monthShort(3)], // cast to int removes leading zero
            ['{DD}', $dayShort(1)], // cast to int removes leading zero
            ['{DD+1}', $dayShort(1)], // cast to int removes leading zero
            ['{DD+2}', $dayShort(2)], // cast to int removes leading zero
            ['{DD+3}', $dayShort(3)], // cast to int removes leading zero
        ];
    }
}
