<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\InvoiceStatusType;
use App\Repository\Query\InvoiceArchiveQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for invoices.
 */
final class InvoiceArchiveForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSearchTermInputField($builder);
        $this->addDateRange($builder, ['timezone' => $options['timezone']]);
        $this->addCustomerMultiChoice($builder, ['required' => false, 'start_date_param' => null, 'end_date_param' => null, 'ignore_date' => true, 'placeholder' => ''], true);
        $builder->add('status', InvoiceStatusType::class, ['required' => false]);
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addOrder($builder);
        $this->addOrderBy($builder, InvoiceArchiveQuery::INVOICE_ARCHIVE_ORDER_ALLOWED);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceArchiveQuery::class,
            'csrf_protection' => false,
            'timezone' => date_default_timezone_get(),
        ]);
    }
}
