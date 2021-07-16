<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\API\BaseApiController;
use App\Utils\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date-time input fields.
 */
class DateTimePickerType extends AbstractType
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
        //$dateTimePicker = $this->localeSettings->getDateTimePickerFormat();
        //$dateTimeFormat = $this->localeSettings->getDateTimeTypeFormat();

        $resolver->setDefaults([
            'label' => 'label.begin',
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'html5' => true,
            'with_seconds' => false,
            'time_increment' => 1,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $presets = [];
        for ($minutes = 0; $minutes <= 1425; $minutes += 15) {
            $h = (int) ($minutes / 60);
            $m = $minutes % 60;
            $interval = new \DateInterval('PT' . $h . 'H' . $m . 'M');
            $presets[] = $interval->format('%H:%I');
        }

        $view->vars['time_presets'] = $presets;

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'placeholder' => strtoupper($options['format']),
            'data-time-picker-increment' => $options['time_increment'],
            'class' => 'datetime-group',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateTimeType::class;
    }
}
