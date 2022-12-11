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
 * Custom form field type to select the minute increment.
 */
final class MinuteIncrementType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('choices', [
            'off' => 0,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            '10' => 10,
            '15' => 15,
            '20' => 20,
            '25' => 25,
            '30' => 30,
            '45' => 45,
            '60' => 60,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
