<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Project;
use App\Form\FormTrait;
use App\Model\QuickEntryModel;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Validator\Constraints\QuickEntryTimesheet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Valid;

class QuickEntryWeekType extends AbstractType
{
    use FormTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $projectOptions = ['label' => false, 'required' => false, 'group_by_submit' => true];
        $this->addProject($builder, true, null, null, $projectOptions);

        $projectFunction = function (FormEvent $event) use ($builder, $projectOptions) {
            $projectFullOptions = $this->buildProjectOptions($projectOptions);
            /** @var QuickEntryModel $data */
            $data = $event->getData();
            if ($data === null || $data->getProject() === null) {
                return;
            }

            $event->getForm()->add('project', ProjectType::class, array_merge($projectFullOptions, [
                'query_builder' => function (ProjectRepository $repo) use ($builder, $data) {
                    // with a pre-selected customer, the project could not be changed any longer => keep it null!
                    $query = new ProjectFormTypeQuery($data->getProject(), null);
                    $query->setUser($builder->getOption('user'));
                    $query->setWithCustomer(true);

                    $begin = clone $data->getFirstEntry()->getBegin();
                    $begin->setTime(0, 0, 0);
                    $query->setProjectStart($begin);

                    $end = clone $data->getLatestEntry()->getBegin();
                    $end->setTime(23, 59, 59);
                    $query->setProjectEnd($end);

                    return $repo->getQueryBuilderForFormType($query);
                },
            ]));
        };

        // a form with records for ended project or invisible activity/project/customer will fail without this listener
        $builder->addEventListener(FormEvents::SUBMIT, $projectFunction);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $projectFunction);

        $activityOptions = ['label' => false, 'required' => false];
        $this->addActivity($builder, null, null, $activityOptions);

        $activityFunction = function (FormEvent $event) use ($builder, $activityOptions) {
            $activityFullOptions = $this->buildActivityOptions($activityOptions);
            /** @var QuickEntryModel $data */
            $data = $event->getData();
            if ($data === null || $data->getActivity() === null) {
                return;
            }

            $event->getForm()->add('activity', ActivityType::class, array_merge($activityFullOptions, [
                'query_builder' => function (ActivityRepository $repo) use ($builder, $data) {
                    $activity = $data->getActivity();
                    $project = $data->getProject();

                    $query = new ActivityFormTypeQuery($activity, $project);
                    $query->setUser($builder->getOption('user'));

                    return $repo->getQueryBuilderForFormType($query);
                },
            ]));
        };

        // a form with records for ended invisible activity/project/customer will fail without this listener
        $builder->addEventListener(FormEvents::SUBMIT, $activityFunction);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $activityFunction);

        $builder->add('timesheets', CollectionType::class, [
            'entry_type' => QuickEntryTimesheetType::class,
            'label' => false,
            'entry_options' => [
                'label' => false,
                'compound' => true,
                'timezone' => $options['timezone'],
                'duration_minutes' => $options['duration_minutes'],
                'duration_hours' => $options['duration_hours'],
            ],
            'allow_add' => true,
            'constraints' => [
                new Valid(),
                new All(['constraints' => [new QuickEntryTimesheet()]])
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder, $options) {
            if ($event->getData() === null) {
                $event->setData(clone $options['prototype_data']);
            }
        });

        $builder->addModelTransformer(new CallbackTransformer(
            function ($transformValue) use ($options) {
                /** @var QuickEntryModel $transformValue */
                if ($transformValue === null || $transformValue->isPrototype()) {
                    return $transformValue;
                }

                $project = $transformValue->getProject();
                $activity = $transformValue->getActivity();

                // this case needs to be handled by the validator
                if ($project === null || $activity === null) {
                    return $transformValue;
                }

                foreach ($transformValue->getTimesheets() as $timesheet) {
                    $timesheet->setUser($transformValue->getUser() ?? $options['user']);
                    $timesheet->setProject($project);
                    $timesheet->setActivity($activity);
                }

                return $transformValue;
            },
            function ($reverseTransformValue) {
                return $reverseTransformValue;
            }
        ));

        // make sure that duration is mapped back to end field
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($options) {
                /** @var QuickEntryModel $data */
                $data = $event->getData();
                $newRecords = $data->getNewTimesheet();
                foreach ($newRecords as $record) {
                    $record->setUser($data->getUser());
                    $record->setProject($data->getProject());
                    $record->setActivity($data->getActivity());
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => QuickEntryModel::class,
            'timezone' => date_default_timezone_get(),
            'duration_minutes' => null,
            'duration_hours' => 10,
            'start_date' => new \DateTime(),
            'prototype_data' => null,
        ]);
    }
}
