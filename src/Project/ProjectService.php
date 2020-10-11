<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Project;

use App\Entity\Customer;
use App\Entity\Project;
use App\Event\ProjectCreateEvent;
use App\Event\ProjectCreatePostEvent;
use App\Event\ProjectCreatePreEvent;
use App\Event\ProjectMetaDefinitionEvent;
use App\Event\ProjectUpdatePostEvent;
use App\Event\ProjectUpdatePreEvent;
use App\Repository\ProjectRepository;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectService
{
    /**
     * @var ProjectRepository
     */
    private $repository;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ProjectRepository $projectRepository, EventDispatcherInterface $dispatcher, ValidatorInterface $validator)
    {
        $this->repository = $projectRepository;
        $this->dispatcher = $dispatcher;
        $this->validator = $validator;
    }

    public function createNewProject(?Customer $customer = null): Project
    {
        $project = new Project();

        if ($customer !== null) {
            $project->setCustomer($customer);
        }

        $this->dispatcher->dispatch(new ProjectMetaDefinitionEvent($project));
        $this->dispatcher->dispatch(new ProjectCreateEvent($project));

        return $project;
    }

    public function saveNewProject(Project $project): Project
    {
        if (null !== $project->getId()) {
            throw new InvalidArgumentException('Cannot create project, already persisted');
        }

        $this->validateProject($project);

        $this->dispatcher->dispatch(new ProjectCreatePreEvent($project));
        $this->repository->saveProject($project);
        $this->dispatcher->dispatch(new ProjectCreatePostEvent($project));

        return $project;
    }

    /**
     * @param Project $project
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    private function validateProject(Project $project, array $groups = []): void
    {
        $errors = $this->validator->validate($project, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors, 'Validation Failed');
        }
    }

    public function updateProject(Project $project): Project
    {
        $this->validateProject($project);

        $this->dispatcher->dispatch(new ProjectUpdatePreEvent($project));
        $this->repository->saveProject($project);
        $this->dispatcher->dispatch(new ProjectUpdatePostEvent($project));

        return $project;
    }

    public function findProjectByName(string $name): ?Project
    {
        return $this->repository->findOneBy(['name' => $name]);
    }
}
