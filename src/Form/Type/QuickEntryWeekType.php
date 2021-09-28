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
        $projectOptions = ['label' => false, 'required' => false];
        $projectFullOptions = $this->buildProjectOptions($projectOptions);

        $builder->add('project', ProjectType::class, array_merge($projectFullOptions, [
            'query_builder' => function (ProjectRepository $repo) use ($builder) {
                $query = new ProjectFormTypeQuery();
                $query->setUser($builder->getOption('user'));
                $query->setWithCustomer(true);

                return $repo->getQueryBuilderForFormType($query);
            },
        ]));

        $projectFunction = function (FormEvent $event) use ($builder, $projectFullOptions) {
            /** @var QuickEntryModel $data */
            $data = $event->getData();
            if ($data === null || $data->getProject() === null) {
                return;
            }
            $event->getForm()->add('project', ProjectType::class, array_merge($projectFullOptions, [
                'query_builder' => function (ProjectRepository $repo) use ($builder, $data) {
                    $project = $data->getProject();
                    $customer = $project->getCustomer();

                    $query = new ProjectFormTypeQuery($project, $customer);
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

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        $builder->addEventListener(FormEvents::SUBMIT, $projectFunction);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $projectFunction);

        $activityOptions = ['label' => false, 'required' => false];
        $activityFullOptions = $this->buildActivityOptions($activityOptions);

        $builder->add('activity', ActivityType::class, array_merge($activityFullOptions, [
            'query_builder' => function (ActivityRepository $repo) use ($builder) {
                $query = new ActivityFormTypeQuery();
                $query->setUser($builder->getOption('user'));

                return $repo->getQueryBuilderForFormType($query);
            },
        ]));

        $activityFunction = function (FormEvent $event) use ($builder, $activityFullOptions) {
            /** @var QuickEntryModel $data */
            $data = $event->getData();
            if ($data === null || $data->getActivity() === null) {
                return;
            }

            $event->getForm()->add('activity', ActivityType::class, array_merge($activityFullOptions, [
                'query_builder' => function (ActivityRepository $repo) use ($builder, $data) {
                    $query = new ActivityFormTypeQuery($data->getActivity(), $data->getProject());
                    $query->setUser($builder->getOption('user'));

                    return $repo->getQueryBuilderForFormType($query);
                },
            ]));
        };

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
            'constraints' => [
// ???                new Valid(),
                new All(['constraints' => [new QuickEntryTimesheet()]])
            ],
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($transformValue) {
                /** @var QuickEntryModel $transformValue */
                if ($transformValue === null || $transformValue->isPrototype()) {
                    return $transformValue;
                }

                $project = $transformValue->getProject();
                $activity = $transformValue->getActivity();

                if ($project === null || $activity === null) {
                    $emptyRow = true;

                    foreach ($transformValue->getTimesheets() as $timesheet) {
                        if ($timesheet->getId() !== null) {
                            $emptyRow = false;
                        }
                    }

                    if (!$emptyRow) {
                        return $transformValue;
                    }

                    //return null;
                    return $transformValue;
                }

                foreach ($transformValue->getTimesheets() as $timesheet) {
                    $timesheet->setUser($transformValue->getUser());
                    $timesheet->setProject($project);
                    $timesheet->setActivity($activity);
                }

                return $transformValue;
            },
            function ($reverseTransformValue) {
                return $reverseTransformValue;
            }
        ));
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
        ]);
    }
}
