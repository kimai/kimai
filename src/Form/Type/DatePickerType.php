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
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date input fields.
 */
class DatePickerType extends AbstractType
{
    public function __construct(private LocaleSettings $localeSettings)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($transform) {
                return $transform;
            },
            function ($reverseTransform) use ($options) {
                if ($reverseTransform === null) {
                    return null;
                }

                if ($reverseTransform instanceof \DateTime && $options['force_time']) {
                    if ($options['force_time'] === 'start') {
                        $reverseTransform->setTime(0, 0, 0);
                    } elseif ($options['force_time'] === 'end') {
                        $reverseTransform->setTime(23, 59, 59);
                    }
                }

                return $reverseTransform;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
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
            'force_time' => null,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-datepickerenable' => 'on',
            'autocomplete' => 'off',
            'placeholder' => strtoupper($options['format']),
            'data-format' => $options['format_picker'],
        ]);
    }

    public function getParent(): string
    {
        return DateType::class;
    }
}
