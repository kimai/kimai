<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the first of the week.
 */
final class FirstWeekDayType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [
            'Monday' => 'monday',
            'Sunday' => 'sunday'
        ];

        $resolver->setDefaults([
            'multiple' => false,
            'choices' => $choices,
            'label' => 'first_weekday',
            'translation_domain' => 'system-configuration',
            'search' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
