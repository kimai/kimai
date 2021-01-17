<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Utils\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date input fields.
 */
class DatePickerType extends AbstractType
{
    private $localeSettings;

    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $pickerFormat = $this->localeSettings->getDatePickerFormat();
        $dateFormat = $this->localeSettings->getDateTypeFormat();

        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'format' => $dateFormat,
            'format_picker' => $pickerFormat,
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => date_default_timezone_get(),
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-datepickerenable' => 'on',
            'autocomplete' => 'off',
            'placeholder' => strtoupper($options['format']),
            'data-format' => $options['format_picker'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateType::class;
    }
}
