<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form;

use AppBundle\Form\Type\UserType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Defines the form used to administrate Timesheet entries.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
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
