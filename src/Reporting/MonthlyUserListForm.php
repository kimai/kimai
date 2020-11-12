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
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
        $builder->add('date', MonthPickerType::class, [
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
            'start_date' => $options['start_date'],
            'format' => $options['format'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MonthlyUserList::class,
            'timezone' => date_default_timezone_get(),
            'start_date' => new \DateTime(),
            'format' => DateType::HTML5_FORMAT,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
