<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Model\QuickEntryModel;
use App\Validator\Constraints\QuickEntryTimesheet;
use DateTime;
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
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $projectOptions = [
            'label' => false,
            'required' => false,
            'join_customer' => true,
            'query_builder_for_user' => true,
            'placeholder' => '',
            'activity_enabled' => true
        ];

        $builder->add('project', ProjectType::class, $projectOptions);

        $projectFunction = function (FormEvent $event) use ($projectOptions) {
            /** @var QuickEntryModel|null $data */
            $data = $event->getData();
            if ($data === null || $data->getProject() === null) {
                return;
            }

            $begin = clone $data->getFirstEntry()->getBegin();
            $begin->setTime(0, 0, 0);
            $projectOptions['project_date_start'] = $begin;

            $end = clone $data->getLatestEntry()->getBegin();
            $end->setTime(23, 59, 59);
            $projectOptions['project_date_end'] = $begin;
            $projectOptions['projects'] = [$data->getProject()];

            $event->getForm()->add('project', ProjectType::class, $projectOptions);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $projectFunction);

        $activityOptions = [
            'label' => false,
            'required' => false,
            'placeholder' => '',
            'query_builder_for_user' => true,
        ];

        $builder->add('activity', ActivityType::class, $activityOptions);

        $activityFunction = function (FormEvent $event) use ($activityOptions) {
            /** @var QuickEntryModel|null $data */
            $data = $event->getData();
            if ($data === null || $data->getActivity() === null) {
                return;
            }

            $activityOptions['activities'] = [$data->getActivity()];
            $activityOptions['projects'] = [$data->getProject()];

            $event->getForm()->add('activity', ActivityType::class, $activityOptions);
        };

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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            if ($event->getData() === null) {
                $event->setData(clone $options['prototype_data']);
            }
        });

        $builder->addModelTransformer(new CallbackTransformer(
            function ($transformValue) use ($options) {
                /** @var QuickEntryModel|null $transformValue */
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
            function (FormEvent $event) {
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
            'start_date' => new DateTime(),
            'prototype_data' => null,
        ]);
    }
}
