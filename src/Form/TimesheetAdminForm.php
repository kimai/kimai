<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\UserType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Defines the form used to administrate Timesheet entries.
 */
class TimesheetAdminForm extends TimesheetEditForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            // User
            ->add('user', UserType::class, [
                'label' => 'label.user',
            ])
        ;
    }
}
