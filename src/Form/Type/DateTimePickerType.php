<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\API\BaseApiController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\ArrayToPartsTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimePickerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $parts = ['year', 'month', 'day', 'hour', 'minute'];
        $dateParts = ['year', 'month', 'day'];
        $timeParts = ['hour', 'minute'];

        // Only pass a subset of the options to children
        $dateOptions = array_intersect_key($options, array_flip([
            'years',
            'months',
            'days',
            'placeholder',
            'choice_translation_domain',
            'required',
            'translation_domain',
            'invalid_message',
            'invalid_message_parameters',
        ]));

        $timeOptions = array_intersect_key($options, array_flip([
            'choice_translation_domain',
            'required',
            'translation_domain',
            'invalid_message',
            'invalid_message_parameters',
        ]));

        if (false === $options['label']) {
            $dateOptions['label'] = false;
            $timeOptions['label'] = false;
        }

        if (null !== $options['date_label']) {
            $dateOptions['label'] = $options['date_label'];
        }

        if (null !== $options['time_label']) {
            $timeOptions['label'] = $options['time_label'];
        }

        $dateOptions['input'] = $timeOptions['input'] = 'array';
        $dateOptions['error_bubbling'] = $timeOptions['error_bubbling'] = true;

        $builder
            ->addViewTransformer(new DataTransformerChain([
                new DateTimeToArrayTransformer($options['model_timezone'], $options['view_timezone'], $parts),
                new ArrayToPartsTransformer([
                    'date' => $dateParts,
                    'time' => $timeParts,
                ]),
            ]))
            ->add('date', DatePickerType::class, $dateOptions)
            ->add('time', TimePickerType::class, $timeOptions)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'documentation' => [
                'type' => 'string',
                'format' => 'date-time',
                'example' => (new \DateTime())->format(BaseApiController::DATE_FORMAT_PHP),
            ],
            'input' => 'datetime',
            'model_timezone' => null,
            'view_timezone' => null,
            // Don't modify \DateTime classes by reference, we treat
            // them like immutable value objects
            'by_reference' => false,
            'error_bubbling' => false,
            // If initialized with a \DateTime object, FormType initializes
            // this option to "\DateTime". Since the internal, normalized
            // representation is not \DateTime, but an array, we need to unset
            // this option.
            'data_class' => null,
            'compound' => true,
            'label' => 'begin',
            'date_label' => null,
            'time_label' => null,
            'empty_data' => function (Options $options) {
                return $options['compound'] ? [] : '';
            },
            'invalid_message' => 'Please enter a valid date and time.',
        ]);

        // Don't add some defaults in order to preserve the defaults
        // set in DateType and TimeType
        $resolver->setDefined([
            'placeholder',
            'choice_translation_domain',
        ]);

        $resolver->setAllowedValues('input', [
            'datetime',
            'string',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'date_time';
    }
}
