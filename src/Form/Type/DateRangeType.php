<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\LocaleService;
use App\Entity\User;
use App\Form\Model\DateRange;
use App\Timesheet\DateTimeFactory;
use App\Utils\FormFormatConverter;
use IntlDateFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a date range
 */
final class DateRangeType extends AbstractType
{
    public const DATE_SPACER = ' - ';

    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'timezone' => date_default_timezone_get(),
            'label' => 'daterange',
            'separator' => self::DATE_SPACER,
            'allow_empty' => true,
            'with_presets' => true,
            'min_day' => null,
            'max_day' => null,
            'locale' => \Locale::getDefault(),
        ]);
        $resolver->setAllowedTypes('separator', 'string');
        $resolver->addAllowedTypes('timezone', 'string');
        $resolver->addAllowedTypes('min_day', ['null', 'string', \DateTimeInterface::class]);
        $resolver->addAllowedTypes('max_day', ['null', 'string', \DateTimeInterface::class]);

        $resolver->setDefault('format', function (Options $options): string {
            $format = $this->localeService->getDateFormat($options['locale']);
            $converter = new FormFormatConverter();

            return $converter->convert($format);
        });
        $resolver->setAllowedTypes('format', ['string']);

        $resolver->setDefault('attr', function (Options $options): array {
            $format = $this->localeService->getDateFormat($options['locale']);
            $converter = new FormFormatConverter();
            $formFormat = $converter->convert($format);
            $pattern = $converter->convertToPattern($formFormat);

            return ['pattern' => $pattern . self::DATE_SPACER . $pattern];
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];
        $factory = DateTimeFactory::createByUser($user);

        if ($options['with_presets']) {
            $ranges = [
                'daterangepicker.allTime' => [null, null],
                'daterangepicker.today' => [$factory->createDateTime('00:00:00'), $factory->createDateTime('23:59:59')],
                'daterangepicker.yesterday' => [$factory->createDateTime('-1 day 00:00:00'), $factory->createDateTime('-1 day 23:59:59')],
                'daterangepicker.thisWeek' => [$factory->getStartOfWeek(), $factory->getEndOfWeek()],
                'daterangepicker.lastWeek' => [$factory->getStartOfWeek('-1 week'), $factory->getEndOfWeek('-1 week')],
                'daterangepicker.thisMonth' => [$factory->getStartOfMonth(), $factory->getEndOfMonth()],
                'daterangepicker.lastMonth' => [$factory->getStartOfLastMonth(), $factory->getEndOfLastMonth()],
                'daterangepicker.thisYearUntilNow' => [$factory->createStartOfYear(), $factory->createDateTime('23:59:59')],
            ];

            $thisYear = (int) $factory->createStartOfYear()->format('Y');
            for ($i = 0; $i < 3; $i++) {
                $year = $thisYear - $i;
                $ranges[$year] = [$year . '-01-01', $year . '-12-31'];
            }

            $view->vars['ranges'] = $ranges;
            $view->vars['rangeFormat'] = $options['format'];
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-separator' => $options['separator'],
        ]);

        if ($options['min_day'] !== null) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'min' => (\is_string($options['min_day']) ? $options['min_day'] : $options['min_day']->format('Y-m-d')), // @phpstan-ignore-line
            ]);
        }

        if ($options['max_day'] !== null) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'max' => (\is_string($options['max_day']) ? $options['max_day'] : $options['max_day']->format('Y-m-d')), // @phpstan-ignore-line
            ]);
        }
    }

    /**
     * @param array{'format': non-empty-string, 'separator': non-empty-string, 'allow_empty': true, 'timezone': non-empty-string} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void // @phpstan-ignore-line
    {
        $formatDate = $options['format'];
        $separator = $options['separator'];
        $allowEmpty = $options['allow_empty'];
        $timezone = $options['timezone'];
        $pattern = (new FormFormatConverter())->convertToPattern($formatDate . $separator . $formatDate, false);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($range) use ($formatDate, $separator, $timezone) {
                $dateFormatter = new IntlDateFormatter(
                    \Locale::getDefault(),
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::MEDIUM,
                    $timezone,
                    IntlDateFormatter::GREGORIAN,
                    $formatDate
                );

                if (null === $range) {
                    return '';
                }

                if (!($range instanceof DateRange)) {
                    throw new \InvalidArgumentException('Invalid DateRange given');
                }

                if (null === $range->getBegin()) {
                    return '';
                }

                $display = $dateFormatter->format($range->getBegin());
                if (null !== $range->getEnd()) {
                    $display .= $separator . $dateFormatter->format($range->getEnd());
                }

                return $display;
            },
            function ($dates) use ($formatDate, $pattern, $separator, $allowEmpty, $timezone) {
                $transformer = new DateTimeToLocalizedStringTransformer(
                    $timezone,
                    $timezone,
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::GREGORIAN,
                    $formatDate
                );

                $fallbackFormat = 'yyyy-MM-dd';
                $fallbackPattern = (new FormFormatConverter())->convertToPattern($fallbackFormat . $separator . $fallbackFormat, false);

                $fallbackTransformer = new DateTimeToLocalizedStringTransformer(
                    $timezone,
                    $timezone,
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::GREGORIAN,
                    $fallbackFormat
                );

                $range = new DateRange();

                if (empty($dates) && $allowEmpty) {
                    return $range;
                }

                if ($dates === null) {
                    throw new TransformationFailedException('Date range missing');
                }

                if (preg_match($pattern, $dates) !== 1 && preg_match($fallbackPattern, $dates) !== 1) {
                    throw new TransformationFailedException('Invalid date range given');
                }
                $values = explode($separator, $dates);

                if (\count($values) !== 2) {
                    throw new TransformationFailedException('Invalid date range given');
                }

                try {
                    $begin = $transformer->reverseTransform($values[0]);
                } catch (TransformationFailedException $e) {
                    // we always allow english format to simplify cross-linking
                    $begin = $fallbackTransformer->reverseTransform($values[0]);
                }
                if ($begin === null) {
                    throw new TransformationFailedException('Invalid begin date given');
                }
                $range->setBegin($begin);

                try {
                    $end = $transformer->reverseTransform($values[1]);
                } catch (TransformationFailedException $e) {
                    // we always allow english format to simplify cross-linking
                    $end = $fallbackTransformer->reverseTransform($values[1]);
                }
                if ($end === null) {
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

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'daterange';
    }
}
