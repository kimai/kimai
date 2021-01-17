<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Timesheet;
use App\Form\Type\DateTimePickerType;
use App\Form\Type\DescriptionType;
use App\Form\Type\DurationType;
use App\Form\Type\FixedRateType;
use App\Form\Type\HourlyRateType;
use App\Form\Type\MetaFieldsCollectionType;
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to manipulate Timesheet entries.
 */
class TimesheetEditForm extends AbstractType
{
    use FormTrait;

    /**
     * @var CustomerRepository
     */
    private $customers;
    /**
     * @var ProjectRepository
     */
    private $projects;

    public function __construct(CustomerRepository $customer, ProjectRepository $project)
    {
        $this->customers = $customer;
        $this->projects = $project;
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
        $begin = null;
        $customerCount = $this->customers->countCustomer(true);
        $timezone = $options['timezone'];
        $isNew = true;

        if (isset($options['data'])) {
            /** @var Timesheet $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = null === $project ? null : $project->getCustomer();

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

        $this->addProject($builder, $isNew, $project, $customer);
        $this->addActivity($builder, $activity, $project);

        $descriptionOptions = ['required' => false];
        if (!$isNew) {
            $descriptionOptions['attr'] = ['autofocus' => 'autofocus'];
        }
        $builder->add('description', DescriptionType::class, $descriptionOptions);

        $this->addTags($builder);
        $this->addRates($builder, $currency, $options);
        $this->addUser($builder, $options);
        $builder->add('metaFields', MetaFieldsCollectionType::class);

        $this->addExported($builder, $options);
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

    protected function addBegin(FormBuilderInterface $builder, array $dateTimeOptions, array $options = [])
    {
        if ($options['begin_minutes'] >= 1 && $options['begin_minutes'] <= 60) {
            $dateTimeOptions['time_increment'] = $options['begin_minutes'];
        }

        $builder->add('begin', DateTimePickerType::class, array_merge($dateTimeOptions, [
            'label' => 'label.begin',
        ]));
    }

    protected function addEnd(FormBuilderInterface $builder, array $dateTimeOptions, array $options = [])
    {
        if ($options['end_minutes'] >= 1 && $options['end_minutes'] <= 60) {
            $dateTimeOptions['time_increment'] = (int) $options['end_minutes'];
        }

        $builder->add('end', DateTimePickerType::class, array_merge($dateTimeOptions, [
            'label' => 'label.end',
            'required' => false,
        ]));
    }

    protected function addDuration(FormBuilderInterface $builder, array $options, bool $forceApply = false, bool $autofocus = false)
    {
        $durationOptions = [
            'required' => false,
            'docu_chapter' => 'timesheet.html#duration-format',
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
                /** @var Timesheet|null $data */
                $data = $event->getData();
                if (null === $data || null === $data->getEnd()) {
                    $event->getForm()->get('duration')->setData(null);
                }
            }
        );

        // make sure that duration is mapped back to end field
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($forceApply) {
                /** @var Timesheet $data */
                $data = $event->getData();
                $duration = $data->getDuration();
                // only apply the duration, if the end is not yet set
                // without that check, the end would be overwritten and the real end time would be lost
                if (($forceApply && null !== $duration) || (null !== $duration && null === $data->getEnd())) {
                    $end = clone $data->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                    $data->setEnd($end);
                }
            }
        );
    }

    protected function addRates(FormBuilderInterface $builder, $currency, array $options)
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

    protected function addUser(FormBuilderInterface $builder, array $options)
    {
        if (!$options['include_user']) {
            return;
        }

        $builder->add('user', UserType::class);
    }

    protected function addExported(FormBuilderInterface $builder, array $options)
    {
        if (!$options['include_exported']) {
            return;
        }

        $builder->add('exported', YesNoType::class, [
            'label' => 'label.exported'
        ]);
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
            'include_user' => false,
            'include_exported' => false,
            'include_rate' => true,
            'docu_chapter' => 'timesheet.html',
            'method' => 'POST',
            'date_format' => null,
            'timezone' => date_default_timezone_get(),
            'customer' => false, // for API usage
            'allow_begin_datetime' => true,
            'allow_end_datetime' => true,
            'allow_duration' => false,
            'duration_minutes' => null,
            'duration_hours' => 10,
            'begin_minutes' => 1,
            'end_minutes' => 1,
            'attr' => [
                'data-form-event' => 'kimai.timesheetUpdate',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);
    }
}
