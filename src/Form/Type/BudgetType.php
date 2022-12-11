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
 * Custom form field type to select the type of budget.
 */
final class BudgetType extends AbstractType
{
    public const TYPE_MONTH = 'month';

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'budgetType',
            // not yet translated in enough languages
            //'placeholder' => 'budgetType_full',
            'required' => false,
            'search' => false,
            'choices' => [
                'budgetType_month' => self::TYPE_MONTH,
            ],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
