<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\ExportQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for exports.
 */
final class ExportToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSearchTermInputField($builder);
        $this->addDateRange($builder, ['timezone' => $options['timezone']]);
        $this->addCustomerMultiChoice($builder, ['start_date_param' => null, 'end_date_param' => null, 'ignore_date' => true], true);
        $this->addProjectMultiChoice($builder, ['ignore_date' => true], true, true);
        $this->addActivityMultiChoice($builder, [], true);
        $this->addTagInputField($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
            $this->addTeamsChoice($builder);
        }
        $this->addTimesheetStateChoice($builder);
        $this->addBillableChoice($builder);
        $this->addExportStateChoice($builder);
        $builder->add('renderer', HiddenType::class, []);
        if ($options['include_export']) {
            $builder->add('markAsExported', HiddenType::class, [
                'label' => 'mark_as_exported',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExportQuery::class,
            'csrf_protection' => false,
            'include_user' => true,
            'include_export' => true,
            'timezone' => date_default_timezone_get(),
        ]);
    }
}
