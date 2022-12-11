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
 * Custom form field type to select a page size.
 */
final class PageSizeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'pageSize',
            'choices' => [
                10 => 10,
                15 => 15,
                20 => 20,
                25 => 25,
                30 => 30,
                35 => 35,
                40 => 40,
                45 => 45,
                50 => 50,
                60 => 60,
                70 => 70,
                80 => 80,
                90 => 90,
                100 => 100,
                125 => 125,
                150 => 150,
                175 => 175,
                200 => 200,
                250 => 250,
                300 => 300,
                350 => 350,
                400 => 400,
                450 => 450,
                500 => 500
            ],
            'placeholder' => null,
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
