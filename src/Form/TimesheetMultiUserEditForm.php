<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\TeamType;
use App\Form\Type\UserType;
use Symfony\Component\Form\FormBuilderInterface;

class TimesheetMultiUserEditForm extends TimesheetAdminEditForm
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['allow_begin_datetime'] = true;
        $options['allow_end_datetime'] = true;
        $options['include_user'] = false;

        parent::buildForm($builder, $options);

        $builder->add('users', UserType::class, [
            'label' => 'users',
            'multiple' => true,
            'required' => false,
        ]);

        $builder->add('teams', TeamType::class, [
            'multiple' => true,
            'required' => false,
        ]);
    }
}
