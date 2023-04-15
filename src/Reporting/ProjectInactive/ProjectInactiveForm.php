<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectInactive;

use App\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ProjectInactiveQuery>
 */
final class ProjectInactiveForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('lastChange', DatePickerType::class, [
            'label' => 'last_record_before',
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectInactiveQuery::class,
            'timezone' => date_default_timezone_get(),
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
