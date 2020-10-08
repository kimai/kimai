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
use App\Form\Type\ActivityType;
use App\Form\Type\CustomerType;
use App\Form\Type\ProjectType;
use App\Form\Type\TagsType;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Defines the form used to manipulate Timesheet entries.
 * @internal
 */
trait FormTrait
{
    protected function addCustomer(FormBuilderInterface $builder, ?Customer $customer = null)
    {
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
            ]);
    }

    protected function addProject(FormBuilderInterface $builder, bool $isNew, ?Project $project = null, ?Customer $customer = null)
    {
        $builder->add('project', ProjectType::class, [
            'placeholder' => '',
            'activity_enabled' => true,
            'query_builder' => function (ProjectRepository $repo) use ($builder, $project, $customer) {
                $query = new ProjectFormTypeQuery($project, $customer);
                $query->setUser($builder->getOption('user'));

                return $repo->getQueryBuilderForFormType($query);
            },
        ]);

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $project, $customer, $isNew) {
                $data = $event->getData();
                $customer = isset($data['customer']) && !empty($data['customer']) ? $data['customer'] : null;
                $project = isset($data['project']) && !empty($data['project']) ? $data['project'] : $project;

                $event->getForm()->add('project', ProjectType::class, [
                    'placeholder' => '',
                    'activity_enabled' => true,
                    'group_by' => null,
                    'query_builder' => function (ProjectRepository $repo) use ($builder, $project, $customer, $isNew) {
                        // is there a better wa to prevent starting a record with a hidden project ?
                        if ($isNew && !empty($project) && (\is_int($project) || \is_string($project))) {
                            /** @var Project $project */
                            $project = $repo->find($project);
                            if (null !== $project) {
                                if (!$project->getCustomer()->isVisible()) {
                                    $customer = null;
                                    $project = null;
                                } elseif (!$project->isVisible()) {
                                    $project = null;
                                }
                            }
                        }
                        $query = new ProjectFormTypeQuery($project, $customer);
                        $query->setUser($builder->getOption('user'));

                        return $repo->getQueryBuilderForFormType($query);
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

    protected function addDescription(FormBuilderInterface $builder)
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
                'attr' => [
                    'autofocus' => 'autofocus'
                ]
            ]);
    }

    protected function addTags(FormBuilderInterface $builder)
    {
        $builder
            ->add('tags', TagsType::class, [
                'required' => false,
            ]);
    }
}
