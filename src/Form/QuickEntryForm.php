<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Configuration\SystemConfiguration;
use App\Form\Type\QuickEntryWeekType;
use App\Model\QuickEntryWeek;
use App\Validator\Constraints\QuickEntryModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Valid;

final class QuickEntryForm extends AbstractType
{
    public function __construct(private readonly SystemConfiguration $configuration)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                // page is loaded, nothing to do
                return $value;
            },
            function (?QuickEntryWeek $value) {
                if ($value === null) {
                    return null;
                }

                foreach ($value->getRows() as $row) {
                    $project = $row->getProject();
                    $activity = $row->getActivity();
                    if ($project === null || $activity === null) {
                        continue;
                    }
                    foreach ($row->getTimesheets() as $timesheet) {
                        $timesheet->setProject($project);
                        $timesheet->setActivity($activity);
                    }
                }

                return $value;
            }
        ));

        $builder->add('rows', CollectionType::class, [
            'label' => false,
            'entry_type' => QuickEntryWeekType::class,
            'entry_options' => [
                'label' => false,
                'duration_minutes' => $this->configuration->getTimesheetIncrementDuration(),
                // this is NOT the start_date, because it would prevent projects from appearing
                // in the first days of the week, if the projects ends at the end of the week
                // the validation still triggers if the user selects days outside the project range
                'start_date' => $options['end_date'],
                'end_date' => $options['end_date'],
                'empty_data' => function (FormInterface $form) use ($options) {
                    return clone $options['prototype_data'];
                },
                'prototype_data' => clone $options['prototype_data'],
            ],
            'prototype_data' => $options['prototype_data'],
            'allow_add' => true,
            'constraints' => [
                new Valid(),
                new All(['constraints' => [new QuickEntryModel()]])
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $start = new \DateTime();
        $start->setTime(0, 0, 0);

        $end = clone $start;
        $end->add(new \DateInterval('P1W'));
        $end->setTime(23, 59, 59);

        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_quick_edit',
            'data_class' => QuickEntryWeek::class,
            'timezone' => date_default_timezone_get(),
            'start_date' => $start,
            'end_date' => $end,
            'prototype_data' => null,
        ]);
    }
}
