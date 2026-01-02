<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\CalendarViewType;
use App\Form\Type\DayPickerType;
use App\Form\Type\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CalendarToolbarForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('date', DayPickerType::class, [
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
        ]);
        $builder->add('view', CalendarViewType::class, []);
        if ($options['change_user']) {
            $builder->add('user', UserType::class, [
                'required' => false,
                'attr' => ['onchange' => 'this.form.submit()']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'timezone' => date_default_timezone_get(),
            'method' => 'GET',
            'change_user' => true,
        ]);
    }
}
