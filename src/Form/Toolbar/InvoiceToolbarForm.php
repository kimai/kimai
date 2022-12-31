<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\DatePickerType;
use App\Form\Type\InvoiceTemplateType;
use App\Repository\Query\InvoiceQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for invoices.
 */
final class InvoiceToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTemplateChoice($builder);
        $this->addDateRange($builder, ['timezone' => $options['timezone']]);
        $this->addCustomerMultiChoice($builder, ['required' => false, 'start_date_param' => null, 'end_date_param' => null, 'ignore_date' => true, 'placeholder' => ''], true);
        $this->addProjectMultiChoice($builder, ['ignore_date' => true], false, true);
        $this->addSearchTermInputField($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
            $this->addTeamsChoice($builder);
        }
        $this->addActivityMultiChoice($builder, $options, true);
        $this->addTagInputField($builder);
        $this->addExportStateChoice($builder);
        $builder->add('invoiceDate', DatePickerType::class, [
            'required' => true,
        ]);
    }

    protected function addTemplateChoice(FormBuilderInterface $builder): void
    {
        $builder->add('template', InvoiceTemplateType::class, [
            'required' => true,
            'placeholder' => null,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceQuery::class,
            'csrf_protection' => false,
            'include_user' => true,
            'timezone' => date_default_timezone_get(),
        ]);
    }
}
