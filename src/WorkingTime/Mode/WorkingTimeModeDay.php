<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Mode;

use App\Entity\User;
use App\Form\Type\DurationType;
use App\WorkingTime\Calculator\WorkingTimeCalculator;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class WorkingTimeModeDay implements WorkingTimeMode
{
    final public const ID = 'day';

    /**
     * @var array<string, string>
     */
    private array $fields = [
        'workHoursMonday' => 'Monday',
        'workHoursTuesday' => 'Tuesday',
        'workHoursWednesday' => 'Wednesday',
        'workHoursThursday' => 'Thursday',
        'workHoursFriday' => 'Friday',
        'workHoursSaturday' => 'Saturday',
        'workHoursSunday' => 'Sunday',
    ];

    public function getId(): string
    {
        return self::ID;
    }

    public function getOrder(): int
    {
        return 10;
    }

    public function getName(): string
    {
        return 'hours_per_day';
    }

    public function getFormFields(): array
    {
        return array_keys($this->fields);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $durationOptions = [
            'required' => false,
            'translation_domain' => 'system-configuration',
            'constraints' => [
                new GreaterThanOrEqual(0),
                new LessThanOrEqual(86400),
            ],
        ];

        foreach ($this->fields as $field => $label) {
            $builder->add($field, DurationType::class, array_merge(['label' => $label], $durationOptions));
        }
    }

    public function getCalculator(User $user): WorkingTimeCalculator
    {
        return new WorkingTimeCalculatorDay($user);
    }
}
