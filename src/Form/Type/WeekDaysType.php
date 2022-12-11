<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select weekdays.
 */
final class WeekDaysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($weekdays): array {
                return explode(',', $weekdays);
            },
            function ($weekdays): string {
                return implode(',', $weekdays);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [
            'Monday' => 'monday',
            'Tuesday' => 'tuesday',
            'Wednesday' => 'wednesday',
            'Thursday' => 'thursday',
            'Friday' => 'friday',
            'Saturday' => 'saturday',
            'Sunday' => 'sunday'
        ];

        $resolver->setDefaults([
            'multiple' => true,
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
