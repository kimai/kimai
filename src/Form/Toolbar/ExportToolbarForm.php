<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\MarkAsExportedType;
use App\Repository\Query\ExportQuery;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for exports.
 */
class ExportToolbarForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addSearchTermInputField($builder);
        $this->addBillableChoice($builder);
        $this->addExportStateChoice($builder);
        $this->addTimesheetStateChoice($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
            $this->addTeamsChoice($builder);
        }
        $this->addDateRange($builder, ['timezone' => $options['timezone']]);
        $this->addCustomerMultiChoice($builder, ['start_date_param' => null, 'end_date_param' => null, 'ignore_date' => true], true);
        $this->addProjectMultiChoice($builder, ['ignore_date' => true], true, true);
        $this->addActivityMultiChoice($builder, [], true);
        $this->addExportRenderer($builder);
        $this->addTagInputField($builder);
        if ($options['include_export']) {
            $builder->add('markAsExported', MarkAsExportedType::class);
        }
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addExportRenderer(FormBuilderInterface $builder)
    {
        $builder->add('renderer', HiddenType::class, []);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
