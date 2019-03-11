<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Entity specific twig extensions.
 * Should be used with caution, as they can trigger a lot of DB queries.
 */
class EntityExtensions extends AbstractExtension
{
    private const UNKNOWN_NAME = '-unknown-';

    /**
     * @var UserRepository|null
     */
    private $users = null;
    /**
     * @var CustomerRepository|null
     */
    private $customers = null;
    /**
     * @var ProjectRepository|null
     */
    private $projects = null;
    /**
     * @var ActivityRepository|null
     */
    private $activities = null;

    /**
     * @param UserRepository $users
     * @param CustomerRepository $customers
     * @param ProjectRepository $projects
     * @param ActivityRepository $activities
     */
    public function __construct(UserRepository $users, CustomerRepository $customers, ProjectRepository $projects, ActivityRepository $activities)
    {
        $this->users = $users;
        $this->customers = $customers;
        $this->projects = $projects;
        $this->activities = $activities;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('user', [$this, 'getUser']),
            new TwigFilter('customer', [$this, 'getCustomer']),
            new TwigFilter('project', [$this, 'getProject']),
            new TwigFilter('activity', [$this, 'getActivity']),
        ];
    }

    /**
     * @param int|User $user
     * @param bool $allowEmpty
     * @return User|null
     */
    public function getUser($user, $allowEmpty = true)
    {
        if ($user instanceof User) {
            return $user;
        }

        $entity = $this->users->getById($user);

        if (null === $entity) {
            $entity = $this->users->loadUserByUsername($user);
        }

        if (null === $entity && false === $allowEmpty) {
            $entity = new User();
            $entity->setUsername(self::UNKNOWN_NAME);
        }

        return $entity;
    }

    /**
     * @param int|Customer $customer
     * @param bool $allowEmpty
     * @return Customer|null
     */
    public function getCustomer($customer, $allowEmpty = true)
    {
        if ($customer instanceof Customer) {
            return $customer;
        }

        $entity = $this->customers->getById($customer);

        if (null === $entity && false === $allowEmpty) {
            $entity = new Customer();
            $entity->setName(self::UNKNOWN_NAME);
        }

        return $entity;
    }

    /**
     * @param int|Project $project
     * @param bool $allowEmpty
     * @return Project|null
     */
    public function getProject($project, $allowEmpty = true)
    {
        if ($project instanceof Project) {
            return $project;
        }

        $entity = $this->projects->getById($project);

        if (null === $entity && false === $allowEmpty) {
            $entity = new Project();
            $entity->setName(self::UNKNOWN_NAME);
            $entity->setCustomer((new Customer())->setName(self::UNKNOWN_NAME));
        }

        return $entity;
    }

    /**
     * @param int|Activity $activity
     * @param bool $allowEmpty
     * @return Activity|null
     */
    public function getActivity($activity, $allowEmpty = true)
    {
        if ($activity instanceof Activity) {
            return $activity;
        }

        $entity = $this->activities->getById($activity);

        if (null === $entity && false === $allowEmpty) {
            $entity = new Activity();
            $entity->setName(self::UNKNOWN_NAME);
        }

        return $entity;
    }
}
