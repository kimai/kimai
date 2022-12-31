<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to set the hourly rate.
 */
final class HourlyRateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // documentation is for NelmioApiDocBundle
            'documentation' => [
                'type' => 'number',
                'description' => 'Hourly rate',
            ],
            'required' => false,
            'label' => 'hourlyRate',
        ]);
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }
}
