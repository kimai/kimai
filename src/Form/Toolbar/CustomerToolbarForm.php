<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Toolbar\VisibilityToolbarForm;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\Query\CustomerQuery;

/**
 * Defines the form used for filtering the customer.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerToolbarForm extends VisibilityToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomerQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
