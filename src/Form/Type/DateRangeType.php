<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Model\DateRange;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a date range
 */
class DateRangeType extends AbstractType
{
    public const DATE_SPACER = ' - ';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;
    /**
     * @var UserDateTimeFactory
     */
    protected $dateFactory;

    /**
     * @param LocaleSettings $localeSettings
     * @param UserDateTimeFactory $dateTime
     */
    public function __construct(LocaleSettings $localeSettings, UserDateTimeFactory $dateTime)
    {
        $this->localeSettings = $localeSettings;
        $this->dateFactory = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $pickerFormat = $this->localeSettings->getDatePickerFormat();
        $dateFormat = $this->localeSettings->getDateFormat();

        $resolver->setDefaults([
            'model_timezone' => null,
            'view_timezone' => null,
            'label' => 'label.daterange',
            'format' => $dateFormat,
            'separator' => self::DATE_SPACER,
            'format_picker' => $pickerFormat,
            'allow_empty' => true,
        ]);

        $resolver->setDefault('attr', function (Options $options) {
            return [
                'autocomplete' => 'off',
                'placeholder' => $options['format_picker'] . $options['separator'] . $options['format_picker'],
                'data-format' => $options['format_picker'],
                'data-separator' => $options['separator'],
            ];
        });
    }

    /**
     * A better way would be to use the Intl NumberFormatter, but if that is not available
     * and the Symfony polyfill is used, this method would not work properly.
     *
     * @param string $string
     * @return string
     */
    protected function convertArabicPersian($string)
    {
        return strtr(
            $string,
            [
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9'
            ]
        );
    }

    protected function formatToPattern(string $format, string $separator)
    {
        $format = preg_quote($format, '/');

        $pattern = str_replace('d', '[0-9]{2}', $format);
        $pattern = str_replace('m', '[0-9]{2}', $pattern);
        $pattern = str_replace('Y', '[0-9]{4}', $pattern);

        return '/^' . $pattern . $separator . $pattern . '$/';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formatDate = $options['format'];
        $separator = $options['separator'];
        $allowEmpty = $options['allow_empty'];
        $pattern = $this->formatToPattern($formatDate, $separator);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($range) use ($formatDate, $separator) {
                if (null === $range) {
                    return '';
                }

                if (!($range instanceof DateRange)) {
                    throw new \InvalidArgumentException('Invalid DateRange given');
                }

                if (null === $range->getBegin()) {
                    return '';
                }

                $display = $range->getBegin()->format($formatDate);
                if (null !== $range->getEnd()) {
                    $display .= $separator . $range->getEnd()->format($formatDate);
                }

                return $display;
            },
            function ($dates) use ($formatDate, $pattern, $separator, $allowEmpty) {
                $range = new DateRange();

                if (empty($dates) && $allowEmpty) {
                    return $range;
                }

                $dates = $this->convertArabicPersian($dates);

                if (preg_match($pattern, $dates) !== 1) {
                    throw new TransformationFailedException('Invalid date range given');
                }

                $values = explode($separator, $dates);

                if (\count($values) !== 2) {
                    throw new TransformationFailedException('Invalid date range given');
                }

                $begin = \DateTime::createFromFormat($formatDate, $values[0], $this->dateFactory->getTimezone());
                if ($begin === false) {
                    throw new TransformationFailedException('Invalid begin date given');
                }
                $range->setBegin($begin);

                $end = \DateTime::createFromFormat($formatDate, $values[1], $this->dateFactory->getTimezone());
                if ($end === false) {
                    throw new TransformationFailedException('Invalid end date given');
                }
                $range->setEnd($end);

                if ($begin->getTimestamp() > $end->getTimestamp()) {
                    throw new TransformationFailedException('Begin date must be before end date');
                }

                return $range;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'daterange';
    }
}
