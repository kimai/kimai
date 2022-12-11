<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\CustomerMonthlyProjects;

use App\Form\Type\CustomerType;
use App\Form\Type\MonthPickerType;
use App\Form\Type\ReportSumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomerMonthlyProjectsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('customer', CustomerType::class, [
            'required' => false,
            'width' => false,
        ]);

        $builder->add('date', MonthPickerType::class, [
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
            'start_date' => $options['start_date'],
        ]);

        $builder->add('sumType', ReportSumType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerMonthlyProjects::class,
            'timezone' => date_default_timezone_get(),
            'start_date' => new \DateTime(),
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
