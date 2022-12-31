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
final class CalendarViewType extends AbstractType
{
    public const DEFAULT_VIEW = 'month';

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [
            'month' => 'month',
            'agendaWeek' => 'agendaWeek',
            'agendaDay' => 'agendaDay',
        ];

        $resolver->setDefaults([
            'required' => true,
            'choices' => $choices,
            'search' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
