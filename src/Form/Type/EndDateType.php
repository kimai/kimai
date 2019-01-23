<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the end-date input field.
 */
class EndDateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.end',
            'widget' => 'single_text',
            'html5' => false,
            'format' => DateType::HTML5_FORMAT,
            'empty_data' => (new \DateTime('last day of this month'))->format('Y-M-d')
        ]);

        $resolver->setDefault('attr', function (Options $options) {
            return [
                'autocomplete' => 'off',
                'data-datepicker' => 'on',
                'placeholder' => $options['format'],
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateType::class;
    }
}
