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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the minute increment.
 */
class MinuteIncrementType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'max_one_hour' => true,
            'minimum' => 5,
        ]);

        $resolver->setDefault('choices', function (Options $options) {
            $choices = [];

            $choices['1'] = 1;
            $choices['2'] = 2;
            $choices['3'] = 3;
            $choices['4'] = 4;
            $choices['5'] = 5;
            $choices['6'] = 6;
            $choices['7'] = 7;
            $choices['8'] = 8;
            $choices['9'] = 9;
            $choices['10'] = 10;
            $choices['15'] = 15;
            $choices['20'] = 20;
            $choices['25'] = 25;
            $choices['30'] = 30;
            $choices['45'] = 45;
            $choices['60'] = 60;

            if (!$options['max_one_hour']) {
                $choices['90'] = 90;
                $choices['100'] = 90;
                $choices['110'] = 90;
                $choices['120'] = 120;
                $choices['130'] = 130;
            }

            $filtered = ['off' => 0];
            foreach ($choices as $name => $value) {
                if ($options['minimum'] <= $value) {
                    $filtered[$name] = $value;
                }
            }

            return $filtered;
        });
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
