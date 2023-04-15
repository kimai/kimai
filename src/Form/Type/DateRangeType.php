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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a date range
 */
final class DateRangeType extends AbstractType
{
    public const DATE_SPACER = ' - ';

    public function __construct(private LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $format = $this->localeService->getDateFormat(\Locale::getDefault());
        $converter = new FormFormatConverter();
        $formFormat = $converter->convert($format);
        $pattern = $converter->convertToPattern($formFormat);

        $resolver->setDefaults([
            'timezone' => date_default_timezone_get(),
            'label' => 'daterange',
            'format' => $formFormat,
            'separator' => self::DATE_SPACER,
            'allow_empty' => true,
            'attr' => [
                'pattern' => $pattern . self::DATE_SPACER . $pattern
            ],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];
        $factory = DateTimeFactory::createByUser($user);

        $view->vars['ranges'] = [
            'today' => [$factory->createDateTime('00:00:00'), $factory->createDateTime('23:59:59')],
            'yesterday' => [$factory->createDateTime('-1 day 00:00:00'), $factory->createDateTime('-1 day 23:59:59')],
            'thisWeek' => [$factory->getStartOfWeek(), $factory->getEndOfWeek()],
            'lastWeek' => [$factory->getStartOfWeek('-1 week'), $factory->getEndOfWeek('-1 week')],
            'thisMonth' => [$factory->getStartOfMonth(), $factory->getEndOfMonth()],
            'lastMonth' => [$factory->getStartOfMonth('-1 month'), $factory->getEndOfMonth('-1 month')],
            'thisYear' => [$factory->createStartOfYear(), $factory->createEndOfYear()],
            'thisYearUntilNow' => [$factory->createStartOfYear(), $factory->createDateTime('23:59:59')],
            'lastYear' => [$factory->createStartOfYear('-1 year'), $factory->createEndOfYear('-1 year')],
            'allTime' => [null, null],
        ];
        $view->vars['rangeFormat'] = $options['format'];

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-separator' => $options['separator'],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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

                $range = new DateRange();

                if (empty($dates) && $allowEmpty) {
                    return $range;
                }

                if ($dates === null) {
                    throw new TransformationFailedException('Date range missing');
                }

                if (preg_match($pattern, $dates) !== 1) {
                    throw new TransformationFailedException('Invalid date range given');
                }
                $values = explode($separator, $dates);

                if (\count($values) !== 2) {
                    throw new TransformationFailedException('Invalid date range given');
                }

                $begin = $transformer->reverseTransform($values[0]);
                if ($begin === null) {
                    throw new TransformationFailedException('Invalid begin date given');
                }
                $range->setBegin($begin);

                $end = $transformer->reverseTransform($values[1]);
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
