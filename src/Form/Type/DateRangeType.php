<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Model\DateRange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a date range
 */
class DateRangeType extends AbstractType
{
    public const DATE_SPACER = ' - ';

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
        $dateFormat = $this->dateSettings[$locale]['date_short'];

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

    protected function formatToPattern(string $format, string $separator)
    {
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
            function (DateRange $range) use ($formatDate, $separator) {
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

                if (preg_match($pattern, $dates) !== 1) {
                    throw new TransformationFailedException('Invalid date range given');
                }

                $values = explode($separator, $dates);

                if (count($values) !== 2) {
                    throw new TransformationFailedException('Invalid date range given');
                }

                $begin = \DateTime::createFromFormat($formatDate, $values[0]);
                if ($begin === false) {
                    throw new TransformationFailedException('Invalid begin date given');
                }
                $range->setBegin($begin);

                $end = \DateTime::createFromFormat($formatDate, $values[1]);
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
