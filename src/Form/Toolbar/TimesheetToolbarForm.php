<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\TimesheetQuery;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the timesheet.
 */
class TimesheetToolbarForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addSearchTermInputField($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
        }
        $this->addDateRangeChoice($builder);
        $this->addCustomerChoice($builder);
        $this->addProjectChoice($builder);
        $this->addActivityChoice($builder);
        $this->addTagInputField($builder);
        $this->addTimesheetStateChoice($builder);
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addHiddenOrder($builder);
        $this->addHiddenOrderBy($builder, TimesheetQuery::TIMESHEET_ORDER_ALLOWED);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimesheetQuery::class,
            'csrf_protection' => false,
            'include_user' => false,
        ]);
    }
}
