<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

class InvoiceModelDefaultHydrator implements InvoiceModelHydrator
{
    public function hydrate(InvoiceModel $model): array
    {
        $currency = $model->getCurrency();
        $tax = $model->getCalculator()->getTax();
        $total = $model->getCalculator()->getTotal();
        $subtotal = $model->getCalculator()->getSubtotal();
        $formatter = $model->getFormatter();

        $values = [
            'invoice.due_date' => $formatter->getFormattedDateTime($model->getDueDate()),
            'invoice.date' => $formatter->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.number' => $model->getInvoiceNumber(),
            'invoice.currency' => $currency,
            'invoice.currency_symbol' => $formatter->getCurrencySymbol($currency),
            'invoice.vat' => $model->getCalculator()->getVat(),
            'invoice.tax' => $formatter->getFormattedMoney($tax, $currency),
            'invoice.tax_nc' => $formatter->getFormattedMoney($tax, null),
            'invoice.tax_plain' => $tax,
            'invoice.total_time' => $formatter->getFormattedDuration($model->getCalculator()->getTimeWorked()),
            'invoice.duration_decimal' => $formatter->getFormattedDecimalDuration($model->getCalculator()->getTimeWorked()),
            'invoice.total' => $formatter->getFormattedMoney($total, $currency),
            'invoice.total_nc' => $formatter->getFormattedMoney($total, null),
            'invoice.total_plain' => $total,
            'invoice.subtotal' => $formatter->getFormattedMoney($subtotal, $currency),
            'invoice.subtotal_nc' => $formatter->getFormattedMoney($subtotal, null),
            'invoice.subtotal_plain' => $subtotal,

            'template.name' => $model->getTemplate()->getName(),
            'template.company' => $model->getTemplate()->getCompany(),
            'template.address' => $model->getTemplate()->getAddress(),
            'template.title' => $model->getTemplate()->getTitle(),
            'template.payment_terms' => $model->getTemplate()->getPaymentTerms(),
            'template.due_days' => $model->getTemplate()->getDueDays(),
            'template.vat_id' => $model->getTemplate()->getVatId(),
            'template.contact' => $model->getTemplate()->getContact(),
            'template.payment_details' => $model->getTemplate()->getPaymentDetails(),

            'query.begin' => $formatter->getFormattedDateTime($model->getQuery()->getBegin()),
            'query.day' => $model->getQuery()->getBegin()->format('d'),
            'query.end' => $formatter->getFormattedDateTime($model->getQuery()->getEnd()),
            'query.month' => $formatter->getFormattedMonthName($model->getQuery()->getBegin()),
            'query.month_number' => $model->getQuery()->getBegin()->format('m'),
            'query.year' => $model->getQuery()->getBegin()->format('Y'),
        ];

        return $values;
    }
}
