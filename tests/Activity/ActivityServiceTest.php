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
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Activity\ActivityService
 */
class ActivityServiceTest extends TestCase
{
    private function getSut(
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?ActivityRepository $repository = null
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

        $service = new ActivityService($repository, $dispatcher, $validator);

        return $service;
    }

    public function testCannotSavePersistedProjectAsNew()
    {
        $project = $this->createMock(Activity::class);
        $project->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create activity, already persisted');

        $sut->saveNewActivity($project);
    }

    public function testsaveNewActivityHasValidationError()
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveNewActivity(new Activity());
    }

    public function testUpdateDispatchesEvents()
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

        $sut->updateActivity($project);
    }

    public function testcreateNewActivityDispatchesEvents()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if ($event instanceof ActivityMetaDefinitionEvent) {
                self::assertInstanceOf(Activity::class, $event->getEntity());
            } elseif ($event instanceof ActivityCreateEvent) {
                self::assertInstanceOf(Activity::class, $event->getActivity());
            } else {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $project = new Project();
        $activity = $sut->createNewActivity($project);

        self::assertSame($project, $activity->getProject());
    }

    public function testsaveNewActivityDispatchesEvents()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if ($event instanceof ActivityCreatePreEvent) {
                self::assertInstanceOf(Activity::class, $event->getActivity());
            } elseif ($event instanceof ActivityCreatePostEvent) {
                self::assertInstanceOf(Activity::class, $event->getActivity());
            } else {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $activity = new Activity();
        $sut->saveNewActivity($activity);
    }

    public function testcreateNewActivityWithoutCustomer()
    {
        $sut = $this->getSut();

        $project = $sut->createNewActivity();
        self::assertNull($project->getProject());

        $project = $sut->createNewActivity();
        self::assertNull($project->getProject());
    }
}
