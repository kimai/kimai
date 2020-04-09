<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractRateForm extends AbstractType
{
    protected function addFields(FormBuilderInterface $builder, ?string $currency)
    {
        $builder
            ->add('user', UserType::class, [
                'required' => false,
            ])
            ->add('rate', MoneyType::class, [
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'number',
                    'description' => 'Rate',
                ],
                'label' => 'label.rate',
                'attr' => [
                    'autofocus' => 'autofocus'
                ],
                'currency' => $currency,
                'help' => 'help.rate',
            ])
            ->add('internalRate', MoneyType::class, [
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'number',
                    'description' => 'Internal rate',
                ],
                'label' => 'label.rate_internal',
                'currency' => $currency,
                'required' => false,
                'help' => 'help.rate_internal',
            ])
            ->add('isFixed', YesNoType::class, [
                'label' => 'label.fixedRate',
                'help' => 'help.fixedRate',
            ])
        ;
    }
}
