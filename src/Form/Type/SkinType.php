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
 * Custom form field type to select the themes skin.
 */
class SkinType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => true,
            'choices' => [
                'blue' => 'blue',
                'black' => 'black',
                'green' => 'green',
                'purple' => 'purple',
                'red' => 'red',
                'yellow' => 'yellow',
                'blue-light' => 'blue-light',
                'black-light' => 'black-light',
                'green-light' => 'green-light',
                'purple-light' => 'purple-light',
                'red-light' => 'red-light',
                'yellow-light' => 'yellow-light',
            ]
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
