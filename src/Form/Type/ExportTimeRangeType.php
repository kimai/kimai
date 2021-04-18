<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select initial export time range.
 */
class ExportTimeRangeType extends AbstractType
{
    /*
     * Using string constants here, since other values may be added in the future that might not follow the
     * "current_*" pattern.
     */
    public const TIME_RANGE_CURRENT_MONTH = 'current_month';
    public const TIME_RANGE_CURRENT_YEAR = 'current_year';
    public const TIME_RANGE_CURRENT_DECADE = 'current_decade';

    public const DEFAULT_TIME_RANGE = self::TIME_RANGE_CURRENT_MONTH;

    public const ALLOWED_TIME_RANGES = [
        self::TIME_RANGE_CURRENT_MONTH,
        self::TIME_RANGE_CURRENT_YEAR,
        self::TIME_RANGE_CURRENT_DECADE,
    ];

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => true,
            'choices' => [
                'option_label.export.time_range.current_month' => static::TIME_RANGE_CURRENT_MONTH,
                'option_label.export.time_range.current_year' => static::TIME_RANGE_CURRENT_YEAR,
                'option_label.export.time_range.current_decade' => static::TIME_RANGE_CURRENT_DECADE,
            ],
            'search' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
