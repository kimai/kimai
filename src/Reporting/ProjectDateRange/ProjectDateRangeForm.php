<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectDateRange;

use App\Form\Type\CustomerType;
use App\Form\Type\MonthPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectDateRangeForm extends AbstractType
{
    /**
     * Simplify cross linking between pages by removing the block prefix.
     *
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('customer', CustomerType::class, [
            'required' => false,
            'label' => false,
            'width' => false,
        ]);

        $builder->add('month', MonthPickerType::class, [
            'label' => false,
            'view_timezone' => $options['timezone'],
            'model_timezone' => $options['timezone'],
        ]);

        $builder->add('includeNoWork', CheckboxType::class, [
            'required' => false,
            'label' => 'label.includeNoWork',
        ]);

        $builder->add('budgetType', ChoiceType::class, [
            'required' => true,
            'multiple' => false,
            'expanded' => true,
            'choices' => [
                'label.budgetIndependent' => null,
                'label.includeNoBudget' => 'none',
                'label.includeBudgetType_full' => 'full',
                'label.includeBudgetType_month' => 'month',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProjectDateRangeQuery::class,
            'timezone' => date_default_timezone_get(),
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
