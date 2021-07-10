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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectViewForm extends AbstractType
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
        $builder->add('includeNoBudget', CheckboxType::class, [
            'required' => false,
            'label' => 'label.includeNoBudget',
        ]);
        $builder->add('includeNoWork', CheckboxType::class, [
            'required' => false,
            'label' => 'label.includeNoWork',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProjectViewQuery::class,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
