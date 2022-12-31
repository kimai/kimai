<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Timesheet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select if a timesheet is billable.
 */
final class TimesheetBillableType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'billable',
            'choices' => [
                'automatic' => Timesheet::BILLABLE_AUTOMATIC,
                'yes' => Timesheet::BILLABLE_YES,
                'no' => Timesheet::BILLABLE_NO,
            ],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
