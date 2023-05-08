<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType as BaseTimezoneType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<string>
 */
final class TimezoneType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'timezone_type';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'timezone',
            'intl' => false,
        ]);
    }

    public function getParent(): string
    {
        return BaseTimezoneType::class;
    }
}
