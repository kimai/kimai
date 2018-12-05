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
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
        $end = null;

        if (isset($options['data'])) {
            /** @var Timesheet $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = null === $entry->getProject() ? null : $entry->getProject()->getCustomer();

            if (null === $project && null !== $activity) {
                $project = $activity->getProject();
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
                'attr' => ['autocomplete' => 'off', 'data-datetimepicker' => 'on'],
            ]);
        }

        if ($options['duration_only']) {
            $builder->add('duration', DurationType::class);
        } else {
            $builder->add('end', DateTimeType::class, [
                'label' => 'label.end',
                'widget' => 'single_text',
                'required' => false,
                'html5' => false,
                'format' => 'yyyy-MM-dd HH:mm',
                'with_seconds' => false,
                'attr' => ['autocomplete' => 'off', 'data-datetimepicker' => 'on'],
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
                    'mapped' => false,
                    'attr' => [
                        'data-related-select' => $this->getBlockPrefix() . '_project',
                        'data-api-url' => ['get_projects', ['customer' => '-s-']],
                    ],
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
            ->add('project', ProjectType::class, array_merge($projectOptions, [
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'integer',
                    'description' => 'Project ID',
                ],
                'required' => true,
                'query_builder' => function (ProjectRepository $repo) use ($project) {
                    return $repo->builderForEntityType($project);
                },
                'attr' => [
                    'data-related-select' => $this->getBlockPrefix() . '_activity',
                    'data-api-url' => ['get_activities', ['project' => '-s-']],
                ],
            ]));

        $builder
            ->add('activity', ActivityType::class, [
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'integer',
                    'description' => 'Activity ID',
                ],
                'query_builder' => function (ActivityRepository $repo) use ($activity) {
                    return $repo->builderForEntityType($activity);
                },
            ])
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
            ])
        ;

        if ($options['include_rate']) {
            $builder
                ->add('fixedRate', NumberType::class, [
                    'label' => 'label.fixed_rate',
                    'required' => false,
                ])
                ->add('hourlyRate', NumberType::class, [
                    'label' => 'label.hourly_rate',
                    'required' => false,
                ]);
        }

        /*
        $builder->get('customer')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $customer = $event->getForm()->getData();
                $event->getForm()->getParent()->add('project', ProjectType::class, [
                    'required' => true,
                    'placeholder' => '',
                    'label' => 'label.project',
                    'query_builder' => function (ProjectRepository $repo) use ($customer) {
                        return $repo->builderForEntityType(null, $customer);
                    },
                    'attr' => [
                        'data-related-select' => $this->getBlockPrefix() . '_activity',
                        'data-api-url' => ['get_activities', ['project' => '-s-']],
                    ],
                ]);
            }
        );
        */

        $builder->get('project')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $project = $event->getForm()->getData();
                $event->getForm()->getParent()->add('activity', ActivityType::class, [
                    'label' => 'label.activity',
                    'query_builder' => function (ActivityRepository $repo) use ($project) {
                        return $repo->builderForEntityType(null, $project);
                    },
                ]);
            }
        );

        if ($options['include_user']) {
            $builder->add('user', UserType::class);
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
            'include_rate' => true,
            'docu_chapter' => 'timesheet',
            'method' => 'POST',
        ]);
    }
}
