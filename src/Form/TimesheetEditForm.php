<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Timesheet;
use App\Form\Type\ActivityType;
use App\Form\Type\CustomerType;
use App\Form\Type\DurationType;
use App\Form\Type\ProjectType;
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
     * @var bool
     */
    private $durationOnly = false;

    /**
     * @param CustomerRepository $customer
     * @param ProjectRepository $project
     * @param bool $durationOnly
     */
    public function __construct(CustomerRepository $customer, ProjectRepository $project, bool $durationOnly)
    {
        $this->customers = $customer;
        $this->projects = $project;
        $this->durationOnly = $durationOnly;
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
        $end = null;

        if (isset($options['data'])) {
            /** @var Timesheet $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = null === $project ? null : $project->getCustomer();

            if (null === $project && null !== $activity) {
                $project = $activity->getProject();
            }

            if (null !== $customer) {
                $currency = $customer->getCurrency();
            }

            $end = $entry->getEnd();
        }

        if (null === $end || !$options['duration_only']) {
            $builder->add('begin', DateTimeType::class, [
                'label' => 'label.begin',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd HH:mm',
                'with_seconds' => false,
                'attr' => ['autocomplete' => 'off', 'data-datetimepicker' => 'on', 'placeholder' => 'yyyy-MM-dd HH:mm'],
            ]);
        }

        if ($options['duration_only']) {
            $builder->add('duration', DurationType::class, [
                'required' => false,
            ]);
        } else {
            $builder->add('end', DateTimeType::class, [
                'label' => 'label.end',
                'widget' => 'single_text',
                'required' => false,
                'html5' => false,
                'format' => 'yyyy-MM-dd HH:mm',
                'with_seconds' => false,
                'attr' => ['autocomplete' => 'off', 'data-datetimepicker' => 'on', 'placeholder' => 'yyyy-MM-dd HH:mm'],
            ]);
        }

        $projectOptions = [];

        if ($this->customers->countCustomer(true) > 1) {
            $builder
                ->add('customer', CustomerType::class, [
                    // documentation is for NelmioApiDocBundle
                    'documentation' => [
                        'type' => 'integer',
                        'description' => 'Customer ID',
                    ],
                    'query_builder' => function (CustomerRepository $repo) use ($customer) {
                        return $repo->builderForEntityType($customer);
                    },
                    'data' => $customer ? $customer : '',
                    'required' => false,
                    'placeholder' => null === $customer ? '' : null,
                    'mapped' => false,
                    'project_enabled' => true,
                ]);
        } else {
            $projectOptions['group_by'] = null;
        }

        if ($this->projects->countProject(true) > 1) {
            $projectOptions['placeholder'] = null;
        } else {
            $projectOptions['group_by'] = null;
        }

        $builder
            ->add(
                'project',
                ProjectType::class,
                array_merge($projectOptions, [
                'activity_enabled' => true,
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'integer',
                    'description' => 'Project ID',
                ],
                'query_builder' => function (ProjectRepository $repo) use ($project, $customer) {
                    return $repo->builderForEntityType($project, $customer);
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
                    'activity_enabled' => true,
                    'group_by' => null,
                    'query_builder' => function (ProjectRepository $repo) use ($data, $project) {
                        return $repo->builderForEntityType($project, $data['customer']);
                    },
                ]);
            }
        );

        $builder
            ->add('activity', ActivityType::class, [
                // documentation is for NelmioApiDocBundle
                'placeholder' => null,
                'documentation' => [
                    'type' => 'integer',
                    'description' => 'Activity ID',
                ],
                'query_builder' => function (ActivityRepository $repo) use ($activity, $project) {
                    return $repo->builderForEntityType($activity, $project);
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
                    'placeholder' => null,
                    'query_builder' => function (ActivityRepository $repo) use ($data, $activity) {
                        return $repo->builderForEntityType($activity, $data['project']);
                    },
                ]);
            }
        );

        $builder
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
            ])
        ;

        if ($options['include_rate']) {
            $builder
                ->add('fixedRate', MoneyType::class, [
                    'label' => 'label.fixed_rate',
                    'required' => false,
                    'currency' => $currency,
                ])
                ->add('hourlyRate', MoneyType::class, [
                    'label' => 'label.hourly_rate',
                    'required' => false,
                    'currency' => $currency,
                ]);
        }

        if ($options['include_user']) {
            $builder->add('user', UserType::class);
        }

        if ($options['include_exported']) {
            $builder->add('exported', YesNoType::class, [
                'label' => 'label.exported'
            ]);
        }
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
            'duration_only' => $this->durationOnly,
            'include_user' => false,
            'include_exported' => false,
            'include_rate' => true,
            'docu_chapter' => 'timesheet',
            'method' => 'POST',
        ]);
    }
}
