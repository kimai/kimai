<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select if something is billable.
 * To be used in combination with the invoicing system.
 */
class BillableType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.billable',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return YesNoType::class;
    }
}
