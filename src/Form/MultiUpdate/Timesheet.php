<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\MultiUpdate;

use App\Form\Type\ActivityType;
use App\Form\Type\CustomerType;
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
use App\Repository\TimesheetRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Timesheet extends AbstractType
{
    /**
     * @var TimesheetRepository
     */
    private $timesheet;
    /**
     * @var CustomerRepository
     */
    private $customers;

    public function __construct(TimesheetRepository $timesheet, CustomerRepository $customer)
    {
        $this->timesheet = $timesheet;
        $this->customers = $customer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $activity = null;
        $project = null;
        $customer = null;
        $customerCount = $this->customers->countCustomer(true);

        if (isset($options['data'])) {
            /** @var TimesheetDTO $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = null === $project ? null : $project->getCustomer();

            if (null === $project && null !== $activity) {
                $project = $activity->getProject();
            }
        }

        $builder
            ->add('customer', CustomerType::class, [
                'query_builder' => function (CustomerRepository $repo) use ($builder, $customer) {
                    $query = new CustomerFormTypeQuery($customer);
                    $query->setUser($builder->getOption('user'));

                    return $repo->getQueryBuilderForFormType($query);
                },
                'data' => $customer ? $customer : '',
                'required' => false,
                'placeholder' => '',
                'mapped' => false,
                'project_enabled' => true,
            ])
        ;

        $projectOptions = [];

        if ($customerCount < 2) {
            $projectOptions['group_by'] = null;
        }

        $builder
            ->add(
                'project',
                ProjectType::class,
                array_merge($projectOptions, [
                    'required' => false,
                    'placeholder' => '',
                    'activity_enabled' => true,
                    'query_builder' => function (ProjectRepository $repo) use ($builder, $project, $customer) {
                        $query = new ProjectFormTypeQuery($project, $customer);
                        $query->setUser($builder->getOption('user'));

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ])
            );

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $project, $customer) {
                $data = $event->getData();
                $customer = isset($data['customer']) && !empty($data['customer']) ? $data['customer'] : null;
                $project = isset($data['project']) && !empty($data['project']) ? $data['project'] : $project;

                $event->getForm()->add('project', ProjectType::class, [
                    'required' => false,
                    'placeholder' => '',
                    'activity_enabled' => true,
                    'group_by' => null,
                    'query_builder' => function (ProjectRepository $repo) use ($builder, $project, $customer) {
                        $query = new ProjectFormTypeQuery($project, $customer);
                        $query->setUser($builder->getOption('user'));

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]);
            }
        );

        $builder
            ->add('activity', ActivityType::class, [
                'required' => false,
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
                    'required' => false,
                    'placeholder' => '',
                    'query_builder' => function (ActivityRepository $repo) use ($data, $activity) {
                        return $repo->getQueryBuilderForFormType(new ActivityFormTypeQuery($activity, $data['project']));
                    },
                ]);
            }
        );

        $builder->add('tags', TagsInputType::class, [
            'required' => false,
        ]);

        if ($options['include_user']) {
            $builder->add('user', UserType::class, [
                'required' => false,
            ]);
        }

        if ($options['include_exported']) {
            $builder->add('exported', YesNoType::class, [
                'label' => 'label.exported'
            ]);
        }

        $builder->add('entities', HiddenType::class, [
            'required' => false,
        ]);

        $builder->get('entities')->addModelTransformer(
            new CallbackTransformer(
                function ($timesheets) {
                    $ids = [];
                    /** @var \App\Entity\Timesheet $timesheet */
                    foreach ($timesheets as $timesheet) {
                        $ids[] = $timesheet->getId();
                    }

                    return implode(',', $ids);
                },
                function ($ids) {
                    if (empty($ids)) {
                        return [];
                    }

                    return $this->timesheet->matching((new Criteria())->where(Criteria::expr()->in('id', explode(',', $ids))));
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimesheetDTO::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_multiupdate',
            'include_user' => false,
            'include_exported' => false,
        ]);
    }
}
