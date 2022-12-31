<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Form\Type\DatePickerType;
use App\Form\Type\DescriptionType;
use App\Form\Type\DurationType;
use App\Form\Type\FixedRateType;
use App\Form\Type\HourlyRateType;
use App\Form\Type\MetaFieldsCollectionType;
use App\Form\Type\TagsType;
use App\Form\Type\TimePickerType;
use App\Form\Type\TimesheetBillableType;
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use App\Repository\CustomerRepository;
use App\Timesheet\Calculator\BillableCalculator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Defines the form used to manipulate Timesheet entries.
 */
class TimesheetEditForm extends AbstractType
{
    use FormTrait;

    public function __construct(private CustomerRepository $customers, private SystemConfiguration $systemConfiguration)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $activity = null;
        $project = null;
        $customer = null;
        $currency = false;
        $customerCount = $this->customers->countCustomer(true);
        $timezone = $options['timezone'];
        $isNew = true;

        if (isset($options['data'])) {
            /** @var Timesheet $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = $project?->getCustomer();

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
            $this->addBegin($builder, $dateTimeOptions, $options);
        }

        if ($options['allow_end_datetime']) {
            $this->addEnd($builder, $dateTimeOptions, $options);
        }

        if ($options['allow_duration']) {
            $this->addDuration($builder, $options, (!$options['allow_begin_datetime'] || !$options['allow_end_datetime']), $isNew);
        }

        if ($this->showCustomer($options, $isNew, $customerCount)) {
            $this->addCustomer($builder, $customer);
        }

        $allowCreate = (bool) $this->systemConfiguration->find('activity.allow_inline_create');

        $this->addProject($builder, $isNew, $project, $customer);
        $this->addActivity($builder, $activity, $project, [
            'allow_create' => $allowCreate && $options['create_activity'],
        ]);

        $descriptionOptions = ['required' => false];
        if (!$isNew) {
            $descriptionOptions['attr'] = ['autofocus' => 'autofocus'];
        }
        $builder->add('description', DescriptionType::class, $descriptionOptions);
        $builder->add('tags', TagsType::class, ['required' => false]);
        $this->addRates($builder, $currency, $options);
        $this->addUser($builder, $options);
        $builder->add('metaFields', MetaFieldsCollectionType::class);

        $this->addExported($builder, $options);
        $this->addBillable($builder, $options);
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

    protected function addBegin(FormBuilderInterface $builder, array $dateTimeOptions, array $options = []): void
    {
        $dateOptions = $dateTimeOptions;
        $builder->add('begin_date', DatePickerType::class, array_merge($dateOptions, [
            'label' => 'date',
            'mapped' => false,
            'constraints' => [
                new NotBlank()
            ]
        ]));

        $timeOptions = $dateTimeOptions;

        $builder->add('begin_time', TimePickerType::class, array_merge($timeOptions, [
            'label' => 'starttime',
            'mapped' => false,
            'constraints' => [
                new NotBlank()
            ]
        ]));

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var Timesheet $timesheet */
                $timesheet = $event->getData();
                $begin = $timesheet->getBegin();

                if (null !== $begin) {
                    $event->getForm()->get('begin_date')->setData($begin);
                    $event->getForm()->get('begin_time')->setData($begin);
                }
            }
        );

        // map single fields to original datetime object
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var Timesheet $data */
                $data = $event->getData();

                /** @var \DateTime|null $date */
                $date = $event->getForm()->get('begin_date')->getData();
                $time = $event->getForm()->get('begin_time')->getData();

                if ($date === null || $time === null) {
                    return;
                }

                // mutable datetime are a problem for doctrine
                $newDate = clone $date;
                $newDate->setTime($time->format('H'), $time->format('i'));

                if ($data->getBegin() === null || $data->getBegin()->getTimestamp() !== $newDate->getTimestamp()) {
                    $data->setBegin($newDate);
                }
            }
        );
    }

    protected function addEnd(FormBuilderInterface $builder, array $dateTimeOptions, array $options = []): void
    {
        $builder->add('end_time', TimePickerType::class, array_merge($dateTimeOptions, [
            'required' => false,
            'label' => 'endtime',
            'mapped' => false
        ]));

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var Timesheet|null $data */
                $data = $event->getData();
                if (null !== $data->getEnd()) {
                    $event->getForm()->get('end_time')->setData($data->getEnd());
                }
            }
        );

        // make sure that date & time fields are mapped back to begin & end fields
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var Timesheet $timesheet */
                $timesheet = $event->getData();
                $oldEnd = $timesheet->getEnd();

                $end = $event->getForm()->get('end_time')->getData();
                if ($end === null || $end === false) {
                    $timesheet->setEnd(null);

                    return;
                }

                // mutable datetime are a problem for doctrine
                $end = clone $end;

                // end is assumed to be the same day then start, if not we raise the day by one
                //$time = $event->getForm()->get('begin_time')->getData();
                $time = $timesheet->getBegin();
                if ($time === null) {
                    throw new \Exception('Cannot work with timesheets without start time');
                }
                $newEnd = clone $time;
                $newEnd->setTime($end->format('H'), $end->format('i'));

                if ($newEnd < $time) {
                    $newEnd->modify('+ 1 day');
                }

                if ($oldEnd === null || $oldEnd->getTimestamp() !== $newEnd->getTimestamp()) {
                    $timesheet->setEnd($newEnd);
                }
            }
        );
    }

    protected function addDuration(FormBuilderInterface $builder, array $options, bool $forceApply = false, bool $autofocus = false): void
    {
        $durationOptions = [
            'required' => false,
            //'toggle' => true,
            'attr' => [
                'placeholder' => '0:00',
            ],
        ];

        if ($autofocus) {
            $durationOptions['attr']['autofocus'] = 'autofocus';
        }

        $duration = $options['duration_minutes'];
        if ($duration !== null && (int) $duration > 0) {
            $durationOptions = array_merge($durationOptions, [
                'preset_minutes' => $duration
            ]);
        }

        $duration = $options['duration_hours'];
        if ($duration !== null && (int) $duration > 0) {
            $durationOptions = array_merge($durationOptions, [
                'preset_hours' => $duration,
            ]);
        }

        $builder->add('duration', DurationType::class, $durationOptions);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var Timesheet|null $timesheet */
                $timesheet = $event->getData();
                if (null === $timesheet || null === $timesheet->getEnd()) {
                    $event->getForm()->get('duration')->setData(null);
                }
            }
        );

        // make sure that duration is mapped back to end field
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($forceApply) {
                /** @var Timesheet $timesheet */
                $timesheet = $event->getData();

                $newDuration = $event->getForm()->get('duration')->getData();
                if ($newDuration !== null && $newDuration > 0 && $newDuration !== $timesheet->getDuration()) {
                    // TODO allow to use a duration that differs from end-start by adding a system configuration check here
                    if ($timesheet->getEnd() === null) {
                        $timesheet->setDuration($newDuration);
                    }
                }

                $duration = $timesheet->getDuration() ?? 0;

                // only apply the duration, if the end is not yet set
                // without that check, the end would be overwritten and the real end time would be lost
                if (($forceApply && $duration > 0) || ($duration > 0 && null === $timesheet->getEnd())) {
                    $end = clone $timesheet->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                    $timesheet->setEnd($end);
                }
            }
        );
    }

    protected function addRates(FormBuilderInterface $builder, $currency, array $options): void
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

    protected function addUser(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['include_user']) {
            return;
        }

        $builder->add('user', UserType::class);
    }

    protected function addExported(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['include_exported']) {
            return;
        }

        $builder->add('exported', YesNoType::class, [
            'label' => 'exported'
        ]);
    }

    protected function addBillable(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_billable']) {
            $builder->add('billableMode', TimesheetBillableType::class, []);
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function (Timesheet $record) {
                if ($record->getBillableMode() === Timesheet::BILLABLE_DEFAULT) {
                    if ($record->isBillable()) {
                        $record->setBillableMode(Timesheet::BILLABLE_YES);
                    } else {
                        $record->setBillableMode(Timesheet::BILLABLE_NO);
                    }
                }

                return $record;
            },
            function (Timesheet $record) {
                $billable = new BillableCalculator();
                $billable->calculate($record, []);

                return $record;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $maxMinutes = $this->systemConfiguration->getTimesheetLongRunningDuration();
        $maxHours = 8;
        if ($maxMinutes > 0) {
            $maxHours = (int) ($maxMinutes / 60);
        }

        $resolver->setDefaults([
            'data_class' => Timesheet::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_edit',
            'include_user' => false,
            'include_exported' => false,
            'include_billable' => true,
            'include_rate' => true,
            'create_activity' => false,
            'docu_chapter' => 'timesheet.html',
            'method' => 'POST',
            'date_format' => null,
            'timezone' => date_default_timezone_get(),
            'customer' => false, // for API usage
            'allow_begin_datetime' => true,
            'allow_end_datetime' => true,
            'allow_duration' => false,
            'duration_minutes' => null,
            'duration_hours' => $maxHours,
            'attr' => [
                'data-form-event' => 'kimai.timesheetUpdate',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);
    }
}
