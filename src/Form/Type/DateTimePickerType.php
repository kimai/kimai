<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\API\BaseApiController;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date-time input fields.
 */
class DateTimePickerType extends AbstractType
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
        $dateTimePicker = $this->localeSettings->getDateTimePickerFormat();
        $dateTimeFormat = $this->localeSettings->getDateTimeTypeFormat();
        $timezone = $this->dateTime->getTimezone()->getName();

        $resolver->setDefaults([
            'documentation' => [
                'type' => 'string',
                'format' => 'date-time',
                'example' => (new \DateTime())->format(BaseApiController::DATE_FORMAT_PHP),
            ],
            'label' => 'label.begin',
            'widget' => 'single_text',
            'html5' => false,
            'format' => $dateTimeFormat,
            'format_picker' => $dateTimePicker,
            'with_seconds' => false,
            'model_timezone' => $timezone,
            'view_timezone' => $timezone,
            'autofocus' => false,
        ]);

        $resolver->setDefault('attr', function (Options $options) {
            $values = [
                'data-datetimepicker' => 'on',
                'autocomplete' => 'off',
                'placeholder' => $options['format'],
                'data-format' => $options['format_picker'],
            ];
            if ($options['autofocus']) {
                $values['autofocus'] = 'autofocus';
            }

            return $values;
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
