<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date-time input fields.
 */
class DateTimePickerType extends AbstractType
{
    /**
     * @var array
     */
    protected $dateSettings;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param array $languageSettings
     */
    public function __construct(RequestStack $requestStack, array $languageSettings)
    {
        $this->requestStack = $requestStack;
        $this->dateSettings = $languageSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        $dateTimePicker = $this->dateSettings[$locale]['date_time_picker'];
        $dateTimeFormat = $this->dateSettings[$locale]['date_time'];

        $resolver->setDefaults([
            'label' => 'label.begin',
            'widget' => 'single_text',
            'html5' => false,
            'format' => $dateTimeFormat,
            'format_picker' => $dateTimePicker,
            'with_seconds' => false,
        ]);

        $resolver->setDefault('attr', function (Options $options) {
            return [
                'data-datetimepicker' => 'on',
                'autocomplete' => 'off',
                'placeholder' => $options['format'],
                'data-format' => $options['format_picker'],
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateTimeType::class;
    }
}
