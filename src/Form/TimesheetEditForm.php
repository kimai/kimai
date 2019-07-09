<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Form\Type\ActivityType;
use App\Form\Type\CustomerType;
use App\Form\Type\DateTimePickerType;
use App\Form\Type\DurationType;
use App\Form\Type\FixedRateType;
use App\Form\Type\HourlyRateType;
use App\Form\Type\MetaFieldsCollectionType;
use App\Form\Type\ProjectType;
use App\Form\Type\TagsInputType;
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Timesheet\UserDateTimeFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to manipulate Timesheet entries.
 */
class TimesheetEditForm extends AbstractType
{
    /**
     * @var CustomerRepository
     */
    private $customers;
    /**
     * @var ProjectRepository
     */
    private $projects;
    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;

    /**
     * @param CustomerRepository $customer
     * @param ProjectRepository $project
     * @param UserDateTimeFactory $dateTime
     */
    public function __construct(CustomerRepository $customer, ProjectRepository $project, UserDateTimeFactory $dateTime)
    {
        $this->customers = $customer;
        $this->projects = $project;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $activity = null;
        $project = null;
        $customer = null;
        $currency = false;
        $begin = null;
        $customerCount = $this->customers->countCustomer(true);
        $projectCount = $this->projects->countProject(true);
        $timezone = $this->dateTime->getTimezone()->getName();
        $isNew = true;

        if (isset($options['data'])) {
            /** @var Timesheet $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = null === $project ? null : $project->getCustomer();

            if (null !== $entry->getId()) {
                $isNew = false;
            }

            if (null === $project && null !== $activity) {
                $project = $activity->getProject();
            }

            if (null !== $customer) {
                $currency = $customer->getCurrency();
            }

            if (null !== ($begin = $entry->getBegin())) {
                $timezone = $begin->getTimezone()->getName();
            }
        }

        $dateTimeOptions = [
            'model_timezone' => $timezone,
            'view_timezone' => $timezone,
        ];

        // primarily for API usage, where we cannot use a user/locale specific format
        if (null !== $options['date_format']) {
            $dateTimeOptions['format'] = $options['date_format'];
        }

        if ($options['allow_begin_datetime']) {
            $this->addBegin($builder, $dateTimeOptions);
        }

        if ($options['allow_duration']) {
            $this->addDuration($builder);
        } elseif ($options['allow_end_datetime']) {
            $this->addEnd($builder, $dateTimeOptions);
        }

        if ($this->showCustomer($options, $isNew, $customerCount)) {
            $this->addCustomer($builder, $customer);
        }

        $this->addProject($builder, $customerCount, $projectCount, $project, $customer);
        $this->addActivity($builder, $activity, $project);
        $this->addDescription($builder);
        $this->addTags($builder);
        $this->addRates($builder, $currency, $options);
        $this->addUser($builder, $options);
        $builder->add('metaFields', MetaFieldsCollectionType::class);

        $this->addExported($builder, $options);
    }

    protected function showCustomer(array $options, bool $isNew, int $customerCount): bool
    {
        if (!$isNew && $options['customer']) {
            return true;
        }

        if ($customerCount < 2) {
            return false;
        }

        if (!$options['customer']) {
            return false;
        }

        return true;
    }

    protected function addCustomer(FormBuilderInterface $builder, ?Customer $customer = null)
    {
        $builder
            ->add('customer', CustomerType::class, [
                'query_builder' => function (CustomerRepository $repo) use ($customer) {
                    return $repo->getQueryBuilderForFormType(new CustomerFormTypeQuery($customer));
                },
                'data' => $customer ? $customer : '',
                'required' => false,
                'placeholder' => '',
                'mapped' => false,
                'project_enabled' => true,
            ]);
    }

    protected function addProject(FormBuilderInterface $builder, int $customerCount, int $projectCount, ?Project $project = null, ?Customer $customer = null)
    {
        $projectOptions = [];

        if ($customerCount < 2) {
            $projectOptions['group_by'] = null;
        }

        if ($projectCount < 2) {
            $projectOptions['group_by'] = null;
        }

        $builder
            ->add(
                'project',
                ProjectType::class,
                array_merge($projectOptions, [
                    'placeholder' => '',
                    'activity_enabled' => true,
                    'query_builder' => function (ProjectRepository $repo) use ($project, $customer) {
                        return $repo->getQueryBuilderForFormType(new ProjectFormTypeQuery($project, $customer));
                    },
                ])
            );

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($project) {
                $data = $event->getData();
                if (!isset($data['customer']) || empty($data['customer'])) {
                    return;
                }

                $event->getForm()->add('project', ProjectType::class, [
                    'placeholder' => '',
                    'activity_enabled' => true,
                    'group_by' => null,
                    'query_builder' => function (ProjectRepository $repo) use ($data, $project) {
                        return $repo->getQueryBuilderForFormType(new ProjectFormTypeQuery($data['project'] ?? $project, $data['customer']));
                    },
                ]);
            }
        );
    }

    protected function addActivity(FormBuilderInterface $builder, ?Activity $activity = null, ?Project $project = null)
    {
        $builder
            ->add('activity', ActivityType::class, [
                'placeholder' => '',
                'query_builder' => function (ActivityRepository $repo) use ($activity, $project) {
                    return $repo->getQueryBuilderForFormType(new ActivityFormTypeQuery($activity, $project));
                },
            ])
        ;

        // replaces the activity select after submission, to make sure only activities for the selected project are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($activity) {
                $data = $event->getData();
                if (!isset($data['project']) || empty($data['project'])) {
                    return;
                }

                $event->getForm()->add('activity', ActivityType::class, [
                    'placeholder' => '',
                    'query_builder' => function (ActivityRepository $repo) use ($data, $activity) {
                        return $repo->getQueryBuilderForFormType(new ActivityFormTypeQuery($activity, $data['project']));
                    },
                ]);
            }
        );
    }

    protected function addBegin(FormBuilderInterface $builder, array $dateTimeOptions)
    {
        $builder->add('begin', DateTimePickerType::class, array_merge($dateTimeOptions, [
            'label' => 'label.begin'
        ]));
    }

    protected function addEnd(FormBuilderInterface $builder, array $dateTimeOptions)
    {
        $builder->add('end', DateTimePickerType::class, array_merge($dateTimeOptions, [
            'label' => 'label.end',
            'required' => false,
        ]));
    }

    protected function addDuration(FormBuilderInterface $builder)
    {
        $builder->add('duration', DurationType::class, [
            'required' => false,
            'docu_chapter' => 'timesheet.html#duration-format',
            'attr' => [
                'placeholder' => '00:00',
            ]
        ]);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var Timesheet $data */
                $data = $event->getData();
                if (null === $data || null === $data->getEnd()) {
                    $event->getForm()->get('duration')->setData(null);
                }
            }
        );

        // make sure that duration is mapped back to end field
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var Timesheet $data */
                $data = $event->getData();
                $duration = $data->getDuration();
                $end = null;
                if (null !== $duration) {
                    $end = clone $data->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                }
                $data->setEnd($end);
            }
        );
    }

    protected function addDescription(FormBuilderInterface $builder)
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
            ]);
    }

    protected function addTags(FormBuilderInterface $builder)
    {
        $builder
            ->add('tags', TagsInputType::class, [
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'string',
                    'description' => 'Comma separated list of tags for this timesheet record',
                ],
                'required' => false,
            ]);
    }

    protected function addRates(FormBuilderInterface $builder, $currency, array $options)
    {
        if (!$options['include_rate']) {
            return;
        }

        $builder
            ->add('fixedRate', FixedRateType::class, [
                'currency' => $currency,
            ])
            ->add('hourlyRate', HourlyRateType::class, [
                'currency' => $currency,
            ]);
    }

    protected function addUser(FormBuilderInterface $builder, array $options)
    {
        if (!$options['include_user']) {
            return;
        }

        $builder->add('user', UserType::class);
    }

    protected function addExported(FormBuilderInterface $builder, array $options)
    {
        if (!$options['include_exported']) {
            return;
        }

        $builder->add('exported', YesNoType::class, [
            'label' => 'label.exported'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Timesheet::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_edit',
            'include_user' => false,
            'include_exported' => false,
            'include_rate' => true,
            'docu_chapter' => 'timesheet.html',
            'method' => 'POST',
            'date_format' => null,
            'customer' => false, // for API usage
            'allow_begin_datetime' => true,
            'allow_end_datetime' => true,
            'allow_duration' => false,
            'attr' => [
                'data-form-event' => 'kimai.timesheetUpdate',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);
    }
}
