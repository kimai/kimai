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
class FirstWeekDayType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [
            'Monday' => 'monday',
            'Sunday' => 'sunday'
        ];

        $resolver->setDefaults([
            'multiple' => false,
            'choices' => $choices,
            'label' => 'first_weekday',
            'translation_domain' => 'system-configuration'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
