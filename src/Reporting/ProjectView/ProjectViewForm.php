<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectView;

use App\Form\Type\CustomerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ProjectViewQuery>
 */
final class ProjectViewForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('customer', CustomerType::class, [
            'required' => false,
            'width' => false,
        ]);
        $builder->add('budgetType', ChoiceType::class, [
            'label' => false,
            'required' => false,
            'placeholder' => null,
            'expanded' => true,
            'choices' => [
                'all' => null,
                'includeWithBudget' => true,
                'includeNoBudget' => false
            ]
        ]);
        $builder->add('includeNoWork', CheckboxType::class, [
            'required' => false,
            'label' => 'includeNoWork',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectViewQuery::class,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
