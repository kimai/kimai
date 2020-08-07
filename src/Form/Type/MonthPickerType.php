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
use App\Utils\MomentFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a month via picker and select previous and next month.
 *
 * Always falls back to the current month if none or an invalid date is given.
 */
final class MonthPickerType extends AbstractType
{
    /**
     * @var LocaleSettings
     */
    private $localeSettings;

    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;

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
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var \DateTime|null $date */
        $date = $form->getData();

        if (null === $date) {
            $date = $this->dateTime->getStartOfMonth();
        }

        $view->vars['month'] = $date;
        $view->vars['previousMonth'] = (clone $date)->modify('-1 month');
        $view->vars['nextMonth'] = (clone $date)->modify('+1 month');
        $view->vars['momentFormat'] = (new MomentFormatConverter())->convert($this->localeSettings->getDateTypeFormat());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'monthpicker';
    }
}
