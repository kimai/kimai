<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\TimesheetQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the timesheet.
 * @extends AbstractType<TimesheetQuery>
 */
final class TimesheetExportToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $newOptions = [];
        if ($options['ignore_date'] === true) {
            $newOptions['ignore_date'] = true;
        }

        $this->addSearchTermInputField($builder);
        $this->addDateRange($builder, ['timezone' => $options['timezone']]);
        $this->addCustomerMultiChoice($builder, $newOptions, true);
        $this->addProjectMultiChoice($builder, $newOptions, true, true);
        $this->addActivityMultiChoice($builder, [], true);
        $this->addTagInputField($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
        }
        $this->addTimesheetStateChoice($builder);
        $this->addBillableChoice($builder);
        $this->addExportStateChoice($builder);
        $this->addOrder($builder);
        $this->addOrderBy($builder, TimesheetQuery::TIMESHEET_ORDER_ALLOWED);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimesheetQuery::class,
            'csrf_protection' => false,
            'include_user' => false,
            'ignore_date' => true,
            'timezone' => date_default_timezone_get(),
        ]);
    }
}
