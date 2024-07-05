<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\YearlyUserList;

use App\Form\Type\ProjectType;
use App\Form\Type\ReportSumType;
use App\Form\Type\TeamType;
use App\Form\Type\YearPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<YearlyUserList>
 */
final class YearlyUserListForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('date', YearPickerType::class, [
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
            'start_date' => $options['start_date'],
            'show_range' => $options['show_range'],
        ]);
        $builder->add('team', TeamType::class, [
            'multiple' => false,
            'required' => false,
            'width' => false,
        ]);
        $builder->add('project', ProjectType::class, [
            'multiple' => false,
            'required' => false,
            'width' => false,
        ]);
        $builder->add('sumType', ReportSumType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => YearlyUserList::class,
            'timezone' => date_default_timezone_get(),
            'start_date' => new \DateTime(),
            'csrf_protection' => false,
            'method' => 'GET',
            'show_range' => false,
        ]);
    }
}
