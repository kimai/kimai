<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Defines the form used for filtering the admin timesheet.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetAdminToolbarForm extends TimesheetToolbarForm
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addTimesheetStateChoice($builder);
        $this->addPageSizeChoice($builder);
        $this->addUserChoice($builder);
        $this->addCustomerChoice($builder);
        $this->addProjectChoice($builder);
        $this->addActivityChoice($builder);
    }
}
