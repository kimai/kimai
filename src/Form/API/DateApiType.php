<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Use this one for API fields, which hold a date without a time (e.g. a "date only" database column).
 * For fields with a time portion use {@see DateTimeApiType} instead.
 */
final class DateApiType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'description' => null,
            'widget' => 'single_text',
            'html5' => true, // for the correct format
        ]);

        $resolver->setDefault('documentation', function (Options $options): array {
            return [
                'type' => 'string',
                'format' => 'date',
                'example' => (new \DateTime())->format('Y-m-d'),
                'description' => $options['description']
            ];
        });
    }

    public function getParent(): string
    {
        return DateType::class;
    }
}
