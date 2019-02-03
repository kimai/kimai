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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date input fields.
 */
class DatePickerType extends AbstractType
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

        $pickerFormat = $this->dateSettings[$locale]['date_picker'];
        $dateFormat = $this->dateSettings[$locale]['date'];

        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'format' => $dateFormat,
            'format_picker' => $pickerFormat,
        ]);

        $resolver->setDefault('attr', function (Options $options) {
            return [
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
        return DateType::class;
    }
}
