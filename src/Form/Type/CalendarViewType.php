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
 * Custom form field type to select a calendar view.
 */
class CalendarViewType extends AbstractType
{
    public const DEFAULT_VIEW = 'month';

    public const ALLOWED_VIEWS = [
        self::DEFAULT_VIEW,
        'agendaWeek',
        'agendaDay',
        'basicWeek',
        'basicDay',
    ];

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach(self::ALLOWED_VIEWS as $name) {
            $choices[$name] = $name;
        }

        $resolver->setDefaults([
            'required' => true,
            'choices' => $choices,
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
