<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

use App\Form\Type\MonthPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonthlyUserListForm extends AbstractType
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
        $builder->add('date', MonthPickerType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MonthlyUserList::class,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
