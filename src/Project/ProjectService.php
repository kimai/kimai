<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Project;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer;
use App\Entity\Project;
use App\Event\ProjectCreateEvent;
use App\Event\ProjectCreatePostEvent;
use App\Event\ProjectCreatePreEvent;
use App\Event\ProjectDeleteEvent;
use App\Event\ProjectMetaDefinitionEvent;
use App\Event\ProjectUpdatePostEvent;
use App\Event\ProjectUpdatePreEvent;
use App\Repository\ProjectRepository;
use App\Utils\Context;
use App\Utils\NumberGenerator;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @final
 */
final class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $repository,
        private readonly SystemConfiguration $configuration,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ValidatorInterface $validator
    )
    {
    }

    public function loadMetaFields(Project $project): void
    {
        $this->dispatcher->dispatch(new ProjectMetaDefinitionEvent($project));
    }

    public function createNewProject(?Customer $customer = null): Project
    {
        $project = new Project();
        $project->setNumber($this->calculateNextProjectNumber());

        if ($customer !== null) {
            $project->setCustomer($customer);
        }

        $this->loadMetaFields($project);
        $this->dispatcher->dispatch(new ProjectCreateEvent($project));

        return $project;
    }

    public function saveProject(Project $project, ?Context $context = null): Project
    {
        if ($project->isNew()) {
            return $this->saveNewProject($project, $context); // @phpstan-ignore method.deprecated
        } else {
            return $this->updateProject($project); // @phpstan-ignore method.deprecated
        }
    }

    /**
     * @deprecated since 2.35 - use saveProject() instead
     */
    public function saveNewProject(Project $project, ?Context $context = null): Project
    {
        if (null !== $project->getId()) {
            throw new InvalidArgumentException('Cannot create project, already persisted');
        }

        $this->validateProject($project);

        if ($context !== null && $this->configuration->isProjectCopyTeamsOnCreate()) {
            foreach ($context->getUser()->getTeams() as $team) {
                $project->addTeam($team);
                $team->addProject($project);
            }
        }

        $this->dispatcher->dispatch(new ProjectCreatePreEvent($project));
        $this->repository->saveProject($project);
        $this->dispatcher->dispatch(new ProjectCreatePostEvent($project));

        return $project;
    }

    public function deleteProject(Project $project, ?Project $replace = null): void
    {
        $this->dispatcher->dispatch(new ProjectDeleteEvent($project, $replace));
        $this->repository->deleteProject($project, $replace);
    }

    /**
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    private function validateProject(Project $project, array $groups = []): void
    {
        $errors = $this->validator->validate($project, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors);
        }
    }

    /**
     * @deprecated since 2.35 - use saveProject() instead
     */
    public function updateProject(Project $project): Project
    {
        $this->validateProject($project);

        $this->dispatcher->dispatch(new ProjectUpdatePreEvent($project));
        $this->repository->saveProject($project);
        $this->dispatcher->dispatch(new ProjectUpdatePostEvent($project));

        return $project;
    }

    public function findProjectByName(string $name, ?Customer $customer): ?Project
    {
        if ($customer !== null) {
            return $this->repository->findOneBy(['name' => $name, 'customer' => $customer->getId()]);
        }

        return $this->repository->findOneBy(['name' => $name]);
    }

    public function findProjectByNumber(string $number): ?Project
    {
        return $this->repository->findOneBy(['number' => $number]);
    }

    public function calculateNextProjectNumber(): ?string
    {
        $format = $this->configuration->find('project.number_format');
        if (empty($format) || !\is_string($format)) {
            return null;
        }

        // we cannot use max(number) because a varchar column returns unexpected results
        $start = $this->repository->countProject();
        $i = 0;
        $createDate = new \DateTimeImmutable();

        do {
            $start++;

            $numberGenerator = new NumberGenerator($format, function (string $originalFormat, string $format, int $increaseBy) use ($start, $createDate): string|int {
                return match ($format) {
                    'Y' => $createDate->format('Y'),
                    'y' => $createDate->format('y'),
                    'M' => $createDate->format('m'),
                    'm' => $createDate->format('n'),
                    'D' => $createDate->format('d'),
                    'd' => $createDate->format('j'),
                    'YY' => (int) $createDate->format('Y') + $increaseBy,
                    'yy' => (int) $createDate->format('y') + $increaseBy,
                    'MM' => (int) $createDate->format('m') + $increaseBy,
                    'DD' => (int) $createDate->format('d') + $increaseBy,
                    'pc' => $start + $increaseBy,
                    default => $originalFormat,
                };
            });

            $number = $numberGenerator->getNumber();
            $project = $this->findProjectByNumber($number);
        } while ($project !== null && $i++ < 100);

        if ($project !== null) {
            return null;
        }

        return $number;
    }
}
