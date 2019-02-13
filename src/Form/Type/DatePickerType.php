<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Timesheet\UserDateTimeFactory;
use App\Utils\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date input fields.
 */
class DatePickerType extends AbstractType
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;

    /**
     * @param LocaleSettings $localeSettings
     * @param UserDateTimeFactory $dateTime
     */
    public function __construct(LocaleSettings $localeSettings, UserDateTimeFactory $dateTime)
    {
        $this->localeSettings = $localeSettings;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $pickerFormat = $this->localeSettings->getDatePickerFormat();
        $dateFormat = $this->localeSettings->getDateTypeFormat();
        $timezone = $this->dateTime->getTimezone()->getName();

        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'format' => $dateFormat,
            'format_picker' => $pickerFormat,
            'model_timezone' => $timezone,
            'view_timezone' => $timezone,
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
