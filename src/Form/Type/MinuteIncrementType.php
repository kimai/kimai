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
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'deactivate' => true,
        ]);

        $resolver->setDefault('choices', function (Options $options) {
            $choices = ['increment_rounding' => null];

            if ($options['deactivate']) {
                $choices['off'] = '0';
            }

            $choices['1'] = '1';
            $choices['2'] = '2';
            $choices['3'] = '3';
            $choices['4'] = '4';
            $choices['5'] = '5';
            $choices['10'] = '10';
            $choices['15'] = '15';
            $choices['20'] = '20';
            $choices['25'] = '25';
            $choices['30'] = '30';
            $choices['45'] = '45';
            $choices['60'] = '60';
            $choices['90'] = '90';
            $choices['120'] = '120';

            return $choices;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
