<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Form\Type\ActivityType;
use App\Form\Type\BillableSearchType;
use App\Form\Type\CustomerType;
use App\Form\Type\DateRangeType;
use App\Form\Type\PageSizeType;
use App\Form\Type\ProjectType;
use App\Form\Type\SearchTermType;
use App\Form\Type\TagsType;
use App\Form\Type\TeamType;
use App\Form\Type\UserRoleType;
use App\Form\Type\UserType;
use App\Form\Type\VisibilityType;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Defines the base form used for all toolbars.
 *
 * Extend this class and stack the elements defined here, they are coupled to each other and with the toolbar.js.
 */
trait ToolbarFormTrait
{
    protected function addUserChoice(FormBuilderInterface $builder): void
    {
        $builder->add('user', UserType::class, [
            'label' => 'user',
            'required' => false,
        ]);
    }

    protected function addUsersChoice(FormBuilderInterface $builder, string $field = 'users', array $options = []): void
    {
        $builder->add($field, UserType::class, array_merge([
            'documentation' => [
                'type' => 'array',
                'items' => ['type' => 'integer', 'description' => 'User ID'],
                'description' => 'Array of user IDs',
            ],
            'label' => 'user',
            'multiple' => true,
            'required' => false,
        ], $options));
    }

    protected function addTeamChoice(FormBuilderInterface $builder): void
    {
        $builder->add('team', TeamType::class, [
            'label' => 'team',
            'required' => false,
        ]);
    }

    protected function addTeamsChoice(FormBuilderInterface $builder, string $field = 'teams', array $options = []): void
    {
        $builder->add($field, TeamType::class, array_merge([
            'documentation' => [
                'type' => 'array',
                'items' => ['type' => 'integer', 'description' => 'Team ID'],
                'description' => 'Array of team IDs',
            ],
            'label' => 'team',
            'multiple' => true,
            'required' => false,
        ], $options));
    }

    protected function addCustomerChoice(FormBuilderInterface $builder, array $options = [], bool $multiProject = false): void
    {
        $this->addCustomerSelect($builder, $options, false, $multiProject);
    }

    protected function addCustomerMultiChoice(FormBuilderInterface $builder, array $options = [], bool $multiProject = false): void
    {
        $this->addCustomerSelect($builder, $options, true, $multiProject);
    }

    private function addCustomerSelect(FormBuilderInterface $builder, array $options, bool $multiCustomer, bool $multiProject): void
    {
        $name = 'customer';
        if ($multiCustomer) {
            $name = 'customers';
        }

        // just a fake field for having this field at the right position in the frontend
        $builder->add($name, CustomerType::class, [
            'documentation' => [
                'type' => 'array',
                'items' => ['type' => 'integer', 'description' => 'Customer ID'],
                'description' => 'Array of customer IDs',
            ],
            'choices' => [],
            'multiple' => $multiCustomer,
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $options, $name, $multiCustomer, $multiProject) {
                /** @var array<string, mixed> $data */
                $data = $event->getData();
                $event->getForm()->add($name, CustomerType::class, array_merge([
                    'multiple' => $multiCustomer,
                    'required' => false,
                    'project_enabled' => $multiCustomer ? 'customers[]' : 'customer',
                    'project_select' => $multiProject ? 'projects' : 'project',
                    'end_date_param' => '%daterange%',
                    'start_date_param' => '%daterange%',
                    'query_builder' => function (CustomerRepository $repo) use ($builder, $data, $name) {
                        $query = new CustomerFormTypeQuery();
                        $query->setUser($builder->getOption('user'));

                        if (\array_key_exists($name, $data) && $data[$name] !== null && $data[$name] !== '') {
                            $customers = \is_array($data[$name]) ? $data[$name] : [$data[$name]];
                            foreach ($customers as $customer) {
                                $customer = \is_string($customer) ? (int) $customer : $customer;
                                if (!\is_int($customer) && !($customer instanceof Customer)) {
                                    throw new \Exception('Need a customer object or an ID for customer select');
                                }
                                $query->addCustomer($customer);
                            }
                        }

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ], $options));
            }
        );
    }

    protected function addVisibilityChoice(FormBuilderInterface $builder, string $label = 'visible'): void
    {
        $builder->add('visibility', VisibilityType::class, [
            'required' => false,
            'placeholder' => null,
            'label' => $label,
            'search' => false
        ]);
    }

    protected function addPageSizeChoice(FormBuilderInterface $builder): void
    {
        $builder->add('pageSize', PageSizeType::class, [
            'required' => false,
            'search' => false
        ]);
    }

    protected function addUserRoleChoice(FormBuilderInterface $builder): void
    {
        $builder->add('role', UserRoleType::class, [
            'required' => false,
        ]);
    }

    protected function addDateRange(FormBuilderInterface $builder, array $options, $allowEmpty = true, $required = false): void
    {
        $params = [
            'required' => $required,
            'allow_empty' => $allowEmpty,
        ];

        if (\array_key_exists('timezone', $options)) {
            $params['timezone'] = $options['timezone'];
        }

        $builder->add('daterange', DateRangeType::class, $params);
    }

    protected function addDateRangeChoice(FormBuilderInterface $builder, $allowEmpty = true, $required = false): void
    {
        $this->addDateRange($builder, [], $allowEmpty, $required);
    }

    protected function addProjectChoice(FormBuilderInterface $builder, array $options = [], bool $multiCustomer = false, bool $multiActivity = false): void
    {
        $this->addProjectSelect($builder, $options, false, $multiCustomer, $multiActivity);
    }

    protected function addProjectMultiChoice(FormBuilderInterface $builder, array $options = [], bool $multiCustomer = false, bool $multiActivity = false): void
    {
        $this->addProjectSelect($builder, $options, true, $multiCustomer, $multiActivity);
    }

    private function addProjectSelect(FormBuilderInterface $builder, array $options, bool $multiProject, bool $multiCustomer, bool $multiActivity): void
    {
        $name = 'project';
        if ($multiProject) {
            $name = 'projects';
        }
        // just a fake field for having this field at the right position in the frontend
        $builder->add($name, ProjectType::class, [
            'documentation' => [
                'type' => 'array',
                'items' => ['type' => 'integer', 'description' => 'Project ID'],
                'description' => 'Array of project IDs',
            ],
            'choices' => [],
            'multiple' => $multiProject,
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $options, $name, $multiCustomer, $multiProject, $multiActivity) {
                /** @var array<string, mixed> $data */
                $data = $event->getData();
                $event->getForm()->add($name, ProjectType::class, array_merge([
                    'multiple' => $multiProject,
                    'required' => false,
                    'activity_enabled' => $multiProject ? 'projects[]' : 'project',
                    'activity_select' => $multiActivity ? 'activities' : 'activity',
                    'query_builder' => function (ProjectRepository $repo) use ($builder, $data, $options, $multiCustomer, $multiProject) {
                        $query = new ProjectFormTypeQuery();
                        $query->setUser($builder->getOption('user'));

                        $name = $multiCustomer ? 'customers' : 'customer';
                        if (\array_key_exists($name, $data) && $data[$name] !== null && $data[$name] !== '') {
                            $customers = \is_array($data[$name]) ? $data[$name] : [$data[$name]];
                            foreach ($customers as $customer) {
                                $customer = \is_string($customer) ? (int) $customer : $customer;
                                if (!\is_int($customer) && !($customer instanceof Customer)) {
                                    throw new \Exception('Need a customer object or an ID for project select');
                                }
                                $query->addCustomer($customer);
                            }
                        }

                        $name = $multiProject ? 'projects' : 'project';
                        if (\array_key_exists($name, $data) && $data[$name] !== null && $data[$name] !== '') {
                            $projects = \is_array($data[$name]) ? $data[$name] : [$data[$name]];
                            foreach ($projects as $project) {
                                $project = \is_string($project) ? (int) $project : $project;
                                if (!\is_int($project) && !($project instanceof Project)) {
                                    throw new \Exception('Need a project object or an ID for project select');
                                }
                                $query->addProject($project);
                            }
                        }

                        if (isset($options['ignore_date']) && true === $options['ignore_date']) {
                            $query->setIgnoreDate(true);
                        }

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ], $options));
            }
        );
    }

    protected function addActivityChoice(FormBuilderInterface $builder, array $options = [], bool $multiProject = false): void
    {
        $this->addActivitySelect($builder, $options, false, $multiProject);
    }

    protected function addActivityMultiChoice(FormBuilderInterface $builder, array $options = [], bool $multiProject = false): void
    {
        $this->addActivitySelect($builder, $options, true, $multiProject);
    }

    private function addActivitySelect(FormBuilderInterface $builder, array $options = [], bool $multiActivity = false, bool $multiProject = false): void
    {
        $name = 'activity';
        if ($multiActivity) {
            $name = 'activities';
        }

        // just a fake field for having this field at the right position in the frontend
        $builder->add($name, ActivityType::class, [
            'documentation' => [
                'type' => 'array',
                'items' => ['type' => 'integer', 'description' => 'Activity ID'],
                'description' => 'Array of activity IDs',
            ],
            'choices' => [],
            'multiple' => $multiActivity,
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($name, $multiActivity, $multiProject) {
                /** @var array<string, mixed> $data */
                $data = $event->getData();
                $event->getForm()->add($name, ActivityType::class, [
                    'multiple' => $multiActivity,
                    'required' => false,
                    'query_builder' => function (ActivityRepository $repo) use ($data, $multiActivity, $multiProject) {
                        $query = new ActivityFormTypeQuery();

                        $name = $multiActivity ? 'activities' : 'activity';
                        if (\array_key_exists($name, $data) && $data[$name] !== null && $data[$name] !== '') {
                            // we need to pre-fetch the activities to see if they are global, see ActivityFormTypeQuery::isGlobalsOnly()
                            $activities = \is_array($data[$name]) ? $data[$name] : [$data[$name]];
                            foreach ($activities as $activity) {
                                $activity = \is_string($activity) ? (int) $activity : $activity;
                                if (!\is_int($activity) && !($activity instanceof Activity)) {
                                    throw new \Exception('Need an activity object or an ID for activity select');
                                }
                                $query->addActivity($activity);
                            }
                        }

                        $name = $multiProject ? 'projects' : 'project';
                        if (\array_key_exists($name, $data) && $data[$name] !== null && $data[$name] !== '') {
                            $projects = \is_array($data[$name]) ? $data[$name] : [$data[$name]];
                            foreach ($projects as $project) {
                                $project = \is_string($project) ? (int) $project : $project;
                                if (!\is_int($project) && !($project instanceof Project)) {
                                    throw new \Exception('Need a project object or an ID for activity select');
                                }
                                $query->addProject($project);
                            }
                        }

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]);
            }
        );
    }

    protected function addHiddenPagination(FormBuilderInterface $builder): void
    {
        $builder->add('page', HiddenType::class, [
            'documentation' => [
                'type' => 'integer',
                'description' => 'Page number. Default: 1',
            ],
            'empty_data' => 1
        ]);
    }

    protected function addOrder(FormBuilderInterface $builder): void
    {
        $builder->add('order', ChoiceType::class, [
            'documentation' => [
                'description' => 'The order for returned items',
            ],
            'label' => 'order',
            'choices' => [
                'asc' => BaseQuery::ORDER_ASC,
                'desc' => BaseQuery::ORDER_DESC
            ],
            'search' => false,
        ]);
    }

    protected function addOrderBy(FormBuilderInterface $builder, array $allowedColumns): void
    {
        $all = [];
        foreach ($allowedColumns as $id => $name) {
            $label = \is_int($id) ? $name : $id;
            $all[$label] = $name;
        }
        $builder->add('orderBy', ChoiceType::class, [
            'label' => 'orderBy',
            'choices' => $all,
            'search' => false,
        ]);
    }

    protected function addTagInputField(FormBuilderInterface $builder): void
    {
        $builder->add('tags', TagsType::class, [
            'required' => false,
            'allow_create' => false,
        ]);
    }

    protected function addSearchTermInputField(FormBuilderInterface $builder): void
    {
        $builder->add('searchTerm', SearchTermType::class);
    }

    protected function addTimesheetStateChoice(FormBuilderInterface $builder): void
    {
        $builder->add('state', ChoiceType::class, [
            'label' => 'entryState',
            'required' => false,
            'placeholder' => null,
            'search' => false,
            'choices' => [
                'all' => TimesheetQuery::STATE_ALL,
                'entryState.running' => TimesheetQuery::STATE_RUNNING,
                'entryState.stopped' => TimesheetQuery::STATE_STOPPED
            ],
        ]);
    }

    protected function addExportStateChoice(FormBuilderInterface $builder): void
    {
        $builder->add('exported', ChoiceType::class, [
            'label' => 'exported',
            'required' => false,
            'placeholder' => null,
            'search' => false,
            'choices' => [
                'all' => TimesheetQuery::STATE_ALL,
                'yes' => TimesheetQuery::STATE_EXPORTED,
                'no' => TimesheetQuery::STATE_NOT_EXPORTED
            ],
        ]);
    }

    protected function addBillableChoice(FormBuilderInterface $builder): void
    {
        $builder->add('billable', BillableSearchType::class, [
            'required' => false,
            'placeholder' => null,
            'search' => false,
        ]);
    }
}
