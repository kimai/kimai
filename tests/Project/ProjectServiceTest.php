<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Project;

use App\Entity\Customer;
use App\Entity\Project;
use App\Event\ProjectCreateEvent;
use App\Event\ProjectCreatePostEvent;
use App\Event\ProjectCreatePreEvent;
use App\Event\ProjectMetaDefinitionEvent;
use App\Event\ProjectUpdatePostEvent;
use App\Event\ProjectUpdatePreEvent;
use App\Project\ProjectService;
use App\Repository\ProjectRepository;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Project\ProjectService
 */
class ProjectServiceTest extends TestCase
{
    private function getSut(
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?ProjectRepository $repository = null
    ): ProjectService {
        if ($repository === null) {
            $repository = $this->createMock(ProjectRepository::class);
        }

        if ($dispatcher === null) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        if ($validator === null) {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->method('validate')->willReturn(new ConstraintViolationList());
        }

        $service = new ProjectService($repository, $dispatcher, $validator);

        return $service;
    }

    public function testCannotSavePersistedProjectAsNew()
    {
        $project = $this->createMock(Project::class);
        $project->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create project, already persisted');

        $sut->saveNewProject($project);
    }

    public function testSaveNewProjectHasValidationError()
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveNewProject(new Project());
    }

    public function testUpdateDispatchesEvents()
    {
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) use ($project) {
            if ($event instanceof ProjectUpdatePostEvent) {
                self::assertSame($project, $event->getProject());
            } elseif ($event instanceof ProjectUpdatePreEvent) {
                self::assertSame($project, $event->getProject());
            } else {
                $this->fail('Invalid event received');
            }
        });

        $sut = $this->getSut($dispatcher);

        $sut->updateProject($project);
    }

    public function testCreateNewProjectDispatchesEvents()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if ($event instanceof ProjectMetaDefinitionEvent) {
                self::assertInstanceOf(Project::class, $event->getEntity());
            } elseif ($event instanceof ProjectCreateEvent) {
                self::assertInstanceOf(Project::class, $event->getProject());
            } else {
                $this->fail('Invalid event received');
            }
        });

        $sut = $this->getSut($dispatcher);

        $customer = new Customer();
        $project = $sut->createNewProject($customer);

        self::assertSame($customer, $project->getCustomer());
    }

    public function testSaveNewProjectDispatchesEvents()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if ($event instanceof ProjectCreatePreEvent) {
                self::assertInstanceOf(Project::class, $event->getProject());
            } elseif ($event instanceof ProjectCreatePostEvent) {
                self::assertInstanceOf(Project::class, $event->getProject());
            } else {
                $this->fail('Invalid event received');
            }
        });

        $sut = $this->getSut($dispatcher);

        $project = new Project();
        $sut->saveNewProject($project);
    }

    public function testCreateNewProjectWithoutCustomer()
    {
        $sut = $this->getSut();

        $project = $sut->createNewProject();
        self::assertNull($project->getCustomer());

        $project = $sut->createNewProject();
        self::assertNull($project->getCustomer());
    }
}
