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
use Symfony\Component\Intl\Countries;

final class InvoiceModelDefaultHydrator implements InvoiceModelHydrator
{
    private const DATE_PROCESS_FORMAT = 'Y-m-d h:i:s';

    public function hydrate(InvoiceModel $model): array
    {
        $template = $model->getTemplate();
        $calculator = $model->getCalculator();
        if ($calculator === null) {
            throw new \InvalidArgumentException('InvoiceModel needs a calculator');
        }

        $currency = $model->getCurrency();
        $totalTax = $calculator->getTax();
        $total = $calculator->getTotal();
        $subtotal = $calculator->getSubtotal();
        $formatter = $model->getFormatter();
        $language = $template->getLanguage();
        if ($language === null) {
            throw new \InvalidArgumentException('InvoiceTemplate needs a language');
        }

        $values = [
            'invoice.due_date' => $formatter->getFormattedDateTime($model->getDueDate()),
            'invoice.due_date_process' => $model->getDueDate()->format(self::DATE_PROCESS_FORMAT), // since 2.14
            'invoice.date' => $formatter->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.date_process' => $model->getInvoiceDate()->format(self::DATE_PROCESS_FORMAT), // since 2.14
            'invoice.number' => $model->getInvoiceNumber(),
            'invoice.currency' => $currency,
            'invoice.language' => $language, // since 1.9
            'invoice.currency_symbol' => $formatter->getCurrencySymbol($currency),
            'invoice.total_time' => $formatter->getFormattedDuration($calculator->getTimeWorked()),
            'invoice.duration_decimal' => $formatter->getFormattedDecimalDuration($calculator->getTimeWorked()),
            'invoice.total' => $formatter->getFormattedMoney($total, $currency),
            'invoice.total_nc' => $formatter->getFormattedMoney($total, $currency, false),
            'invoice.total_plain' => $total,
            'invoice.subtotal' => $formatter->getFormattedMoney($subtotal, $currency),
            'invoice.subtotal_nc' => $formatter->getFormattedMoney($subtotal, $currency, false),
            'invoice.subtotal_plain' => $subtotal,

            'template.name' => $template->getName() ?? '',
            'template.company' => $template->getCompany() ?? '', // deprecated - cannot be deleted, referenced in customer templates
            'template.address' => $template->getAddress() ?? '', // deprecated - cannot be deleted, referenced in customer templates
            'template.title' => $template->getTitle() ?? '',
            'template.payment_terms' => $template->getPaymentTerms() ?? '',
            'template.due_days' => $template->getDueDays(),
            'template.vat_id' => $template->getVatId() ?? '', // deprecated - cannot be deleted, referenced in customer templates
            'template.contact' => $template->getContact() ?? '',
            'template.country' => null,
            'template.country_name' => null,
            'template.payment_details' => $template->getPaymentDetails() ?? '',

            'query.begin' => '',
            'query.begin_day' => '',
            'query.begin_process' => null, // since 2.14
            'query.begin_month' => '',
            'query.begin_month_number' => '',
            'query.begin_year' => '',
            'query.end' => '', // since 1.9
            'query.end_day' => '', // since 1.9
            'query.end_process' => null, // since 2.14
            'query.end_month' => '', // since 1.9
            'query.end_month_number' => '', // since 1.9
            'query.end_year' => '', // since 1.9

            // since 2.0.15
            'user.see_others' => ($model->getQuery()?->getUser() === null),
        ];

        $taxRows = $calculator->getTaxRows();

        $values['invoice.tax_rows'] = [];
        $counter = 1;
        foreach ($taxRows as $taxRow) {
            $tax = $taxRow->getTax();
            $values['invoice.tax_rows'][] = [
                'counter' => $counter++,
                'type' => $tax->getType()->value,
                'name' => $tax->getName(),
                'rate' => $tax->getRate(),
                'note' => $tax->getNote(),
                'show' => $tax->isShow(),
                'currency' => $currency,
                'amount' => $taxRow->getAmount(), // do not format, only available in twig anyway
                'base' => $taxRow->getBasePrice(), // do not format, only available in twig anyway
            ];
        }

        // this must be kept for BC reasons, many templates access these values
        if (\count($taxRows) > 0) {
            $taxRow = $taxRows[0];
            $tax = $taxRow->getTax();
            $totalTax = $taxRow->getAmount();

            $values['invoice.vat'] = $tax->getRate();
            $values['invoice.tax_hide'] = !$tax->isShow();
            $values['invoice.tax'] = $formatter->getFormattedMoney($totalTax, $currency);
            $values['invoice.tax_nc'] = $formatter->getFormattedMoney($totalTax, $currency, false);
            $values['invoice.tax_plain'] = $totalTax;
        }

        $seller = $template->getCustomer();
        if ($seller !== null) {
            $country = $seller->getCountry();
            if ($country !== null) {
                $values['template.country'] = $country; // deprecated - cannot be deleted, referenced in customer templates
                $values['template.country_name'] = Countries::getName($country, $language); // deprecated - cannot be deleted, referenced in customer templates
            }
        }

        $query = $model->getQuery();
        if ($query !== null) {
            $begin = $query->getBegin();
            if ($begin !== null) {
                $values = array_merge($values, [
                    'query.day' => $begin->format('d'),
                    'query.month' => $formatter->getFormattedMonthName($begin), // deprecated - cannot be deleted, referenced in customer templates
                    'query.month_number' => $begin->format('m'), // deprecated - cannot be deleted, referenced in customer templates
                    'query.year' => $begin->format('Y'), // deprecated - cannot be deleted, referenced in customer templates
                    'query.begin' => $formatter->getFormattedDateTime($begin), // deprecated - cannot be deleted, referenced in customer templates
                    'query.begin_process' => $begin->format(self::DATE_PROCESS_FORMAT),
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
