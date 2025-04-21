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

final class InvoiceModelDefaultHydrator implements InvoiceModelHydrator
{
    private const DATE_PROCESS_FORMAT = 'Y-m-d h:i:s';

    public function hydrate(InvoiceModel $model): array
    {
        $template = $model->getTemplate();
        if ($template === null) {
            throw new \InvalidArgumentException('InvoiceModel needs a template');
        }

        $currency = $model->getCurrency();
        $tax = $model->getCalculator()->getTax();
        $total = $model->getCalculator()->getTotal();
        $subtotal = $model->getCalculator()->getSubtotal();
        $formatter = $model->getFormatter();
        $language = $template->getLanguage();

        $values = [
            'invoice.due_date' => $formatter->getFormattedDateTime($model->getDueDate()),
            'invoice.due_date_process' => $model->getDueDate()->format(self::DATE_PROCESS_FORMAT), // since 2.14
            'invoice.date' => $formatter->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.date_process' => $model->getInvoiceDate()->format(self::DATE_PROCESS_FORMAT), // since 2.14
            'invoice.number' => $model->getInvoiceNumber(),
            'invoice.currency' => $currency,
            'invoice.language' => $language, // since 1.9
            'invoice.currency_symbol' => $formatter->getCurrencySymbol($currency),
            'invoice.vat' => $model->getCalculator()->getVat(),
            'invoice.tax_hide' => $model->isHideZeroTax() && $tax === 0.00,
            'invoice.tax' => $formatter->getFormattedMoney($tax, $currency),
            'invoice.tax_nc' => $formatter->getFormattedMoney($tax, $currency, false),
            'invoice.tax_plain' => $tax,
            'invoice.total_time' => $formatter->getFormattedDuration($model->getCalculator()->getTimeWorked()),
            'invoice.duration_decimal' => $formatter->getFormattedDecimalDuration($model->getCalculator()->getTimeWorked()),
            'invoice.total' => $formatter->getFormattedMoney($total, $currency),
            'invoice.total_nc' => $formatter->getFormattedMoney($total, $currency, false),
            'invoice.total_plain' => $total,
            'invoice.subtotal' => $formatter->getFormattedMoney($subtotal, $currency),
            'invoice.subtotal_nc' => $formatter->getFormattedMoney($subtotal, $currency, false),
            'invoice.subtotal_plain' => $subtotal,

            'template.name' => $template->getName() ?? '',
            'template.company' => $template->getCompany() ?? '',
            'template.address' => $template->getAddress() ?? '',
            'template.title' => $template->getTitle() ?? '',
            'template.payment_terms' => $template->getPaymentTerms() ?? '',
            'template.due_days' => $template->getDueDays(),
            'template.vat_id' => $template->getVatId() ?? '',
            'template.contact' => $template->getContact() ?? '',
            'template.country' => null,
            'template.country_name' => null,
            'template.payment_details' => $template->getPaymentDetails() ?? '',

            'query.begin' => '',
            'query.begin_day' => '',
            'query.begin_process' => null,         // since 2.14
            'query.begin_month' => '',
            'query.begin_month_number' => '',
            'query.begin_year' => '',
            'query.end' => '',                  // since 1.9
            'query.end_day' => '',              // since 1.9
            'query.end_process' => null,           // since 2.14
            'query.end_month' => '',            // since 1.9
            'query.end_month_number' => '',     // since 1.9
            'query.end_year' => '',             // since 1.9

            // since 2.0.15
            'user.see_others' => ($model->getQuery()?->getUser() === null),
        ];

        $query = $model->getQuery();
        if ($query !== null) {
            $begin = $query->getBegin();
            if ($begin !== null) {
                $values = array_merge($values, [
                    'query.day' => $begin->format('d'),
                    // @deprecated - but impossible to delete
                    'query.month' => $formatter->getFormattedMonthName($begin),
                    // @deprecated - but impossible to delete
                    'query.month_number' => $begin->format('m'),
                    // @deprecated - but impossible to delete
                    'query.year' => $begin->format('Y'),
                    // @deprecated - but impossible to delete
                    'query.begin' => $formatter->getFormattedDateTime($begin),
                    'query.begin_process' => $begin->format(self::DATE_PROCESS_FORMAT), // since 2.14
                    'query.begin_day' => $begin->format('d'),
                    'query.begin_month' => $formatter->getFormattedMonthName($begin),
                    'query.begin_month_number' => $begin->format('m'),
                    'query.begin_year' => $begin->format('Y'),
                ]);
            }

            $end = $query->getEnd();
            if ($end !== null) {
                $values = array_merge($values, [
                    'query.end' => $formatter->getFormattedDateTime($end),
                    'query.end_process' => $end->format(self::DATE_PROCESS_FORMAT), // since 2.14
                    'query.end_day' => $end->format('d'),
                    'query.end_month' => $formatter->getFormattedMonthName($end),
                    'query.end_month_number' => $end->format('m'),
                    'query.end_year' => $end->format('Y'),
                ]);
            }

            // since 2.0.15
            $activity = $query->getActivity();
            if ($activity !== null) {
                $prefix = 'query.activity.';

                $values = array_merge($values, [
                    $prefix . 'name' => $activity->getName() ?? '',
                    $prefix . 'comment' => $activity->getComment() ?? '',
                ]);
            }

            // since 2.0.15
            $project = $query->getProject();
            if ($project !== null) {
                $prefix = 'query.project.';

                $values = array_merge($values, [
                    $prefix . 'name' => $project->getName() ?? '',
                    $prefix . 'comment' => $project->getComment() ?? '',
                    $prefix . 'order_number' => $project->getOrderNumber(),
                ]);
            }
        }

        $entries = $model->getEntries();
        $min = null;
        $max = null;

        foreach ($entries as $entry) {
            if ($min === null || $min->getBegin() > $entry->getBegin()) {
                $min = $entry;
            }

            if ($max === null || $max->getBegin() < $entry->getBegin()) {
                $max = $entry;
            }
        }

        if ($min !== null && $max !== null) {
            $values = array_merge($values, [
                'invoice.first' => $formatter->getFormattedDateTime($min->getBegin()),
                'invoice.first_process' => $min->getBegin()?->format(self::DATE_PROCESS_FORMAT), // since 2.14
                'invoice.last' => $formatter->getFormattedDateTime($max->getEnd()),
                'invoice.last_process' => $max->getEnd()?->format(self::DATE_PROCESS_FORMAT), // since 2.14
            ]);
        }

        return $values;
    }
}
