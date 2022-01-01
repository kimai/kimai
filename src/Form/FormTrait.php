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
use App\Form\Type\DescriptionType;
use App\Form\Type\ProjectType;
use App\Form\Type\TagsType;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectFormTypeQuery;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Helper functions to manage dependent customer-project-activity fields.
 *
 * If you always want to show the list of all available projects/activities, use the form types directly.
 */
trait FormTrait
{
    protected function addCustomer(FormBuilderInterface $builder, ?Customer $customer = null)
    {
        $builder->add('customer', CustomerType::class, [
            'query_builder_for_user' => true,
            'customers' => $customer,
            'data' => $customer ? $customer : '',
            'required' => false,
            'placeholder' => '',
            'mapped' => false,
            'project_enabled' => true,
        ]);
    }

    protected function addProject(FormBuilderInterface $builder, bool $isNew, ?Project $project = null, ?Customer $customer = null, array $options = [])
    {
        $options = array_merge([
            'placeholder' => '',
            'activity_enabled' => true,
            'query_builder_for_user' => true,
            'join_customer' => true
        ], $options);

        $builder->add('project', ProjectType::class, array_merge($options, [
            'projects' => $project,
            'customers' => $customer,
        ]));

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $project, $customer, $isNew, $options) {
                $data = $event->getData();
                $customer = isset($data['customer']) && !empty($data['customer']) ? $data['customer'] : null;
                $project = isset($data['project']) && !empty($data['project']) ? $data['project'] : $project;

                $event->getForm()->add('project', ProjectType::class, array_merge($options, [
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
                        $query->setWithCustomer(true);

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]));
            }
        );
    }

    protected function addActivity(FormBuilderInterface $builder, ?Activity $activity = null, ?Project $project = null, array $options = [])
    {
        $options = array_merge(['placeholder' => '', 'query_builder_for_user' => true], $options);

        $options['projects'] = $project;
        $options['activities'] = $activity;

        $builder->add('activity', ActivityType::class, $options);

        // replaces the activity select after submission, to make sure only activities for the selected project are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                if (!isset($data['project']) || empty($data['project'])) {
                    return;
                }

                $options['projects'] = $data['project'];

                $event->getForm()->add('activity', ActivityType::class, $options);
            }
        );
    }

    /**
     * @deprecated since 1.13
     */
    protected function addDescription(FormBuilderInterface $builder)
    {
        @trigger_error('FormTrait::addDescription() is deprecated and will be removed with 2.0, use DescriptionType instead', E_USER_DEPRECATED);

        $builder->add('description', DescriptionType::class, [
            'required' => false,
            'attr' => [
                'autofocus' => 'autofocus'
            ]
        ]);
    }

    /**
     * @deprecated since 1.14
     */
    protected function addTags(FormBuilderInterface $builder)
    {
        @trigger_error('FormTrait::addTags() is deprecated and will be removed with 2.0, use TagsType instead', E_USER_DEPRECATED);

        $builder->add('tags', TagsType::class, [
            'required' => false,
        ]);
    }
}
