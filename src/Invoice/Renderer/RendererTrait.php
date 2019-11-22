<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\InvoiceItem;
use App\Invoice\InvoiceModel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

trait RendererTrait
{
    /**
     * @return string[]
     */
    abstract protected function getFileExtensions();

    /**
     * @return string
     */
    abstract protected function getContentType();

    /**
     * @param InvoiceDocument $document
     * @return bool
     */
    public function supports(InvoiceDocument $document): bool
    {
        foreach ($this->getFileExtensions() as $extension) {
            if (stripos($document->getFilename(), $extension) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    abstract protected function getFormattedDateTime(\DateTime $date);

    /**
     * @param \DateTime $date
     * @return mixed
     */
    abstract protected function getFormattedTime(\DateTime $date);

    /**
     * @param int $amount
     * @param string|null $currency
     * @return mixed
     */
    abstract protected function getFormattedMoney($amount, $currency);

    /**
     * @param \DateTime $date
     * @return mixed
     */
    abstract protected function getFormattedMonthName(\DateTime $date);

    /**
     * @param int $seconds
     * @return mixed
     */
    abstract protected function getFormattedDuration($seconds);

    /**
     * @param int $seconds
     * @return mixed
     */
    abstract protected function getFormattedDecimalDuration($seconds);

    /**
     * @param InvoiceModel $model
     * @return array
     */
    protected function modelToReplacer(InvoiceModel $model)
    {
        $customer = $model->getCustomer();
        $project = $model->getQuery()->getProject();
        $activity = $model->getQuery()->getActivity();
        $currency = $model->getCalculator()->getCurrency();
        $tax = $model->getCalculator()->getTax();
        $total = $model->getCalculator()->getTotal();
        $subtotal = $model->getCalculator()->getSubtotal();

        $values = [
            'invoice.due_date' => $this->getFormattedDateTime($model->getDueDate()),
            'invoice.date' => $this->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.number' => $model->getNumberGenerator()->getInvoiceNumber(),
            'invoice.currency' => $currency,
            'invoice.vat' => $model->getCalculator()->getVat(),
            'invoice.tax' => $this->getFormattedMoney($tax, $currency),
            'invoice.tax_nc' => $this->getFormattedMoney($tax, null),
            'invoice.total_time' => $this->getFormattedDuration($model->getCalculator()->getTimeWorked()),
            'invoice.duration_decimal' => $this->getFormattedDecimalDuration($model->getCalculator()->getTimeWorked()),
            'invoice.total' => $this->getFormattedMoney($total, $currency),
            'invoice.total_nc' => $this->getFormattedMoney($total, null),
            'invoice.subtotal' => $this->getFormattedMoney($subtotal, $currency),
            'invoice.subtotal_nc' => $this->getFormattedMoney($subtotal, null),

            'template.name' => $model->getTemplate()->getName(),
            'template.company' => $model->getTemplate()->getCompany(),
            'template.address' => $model->getTemplate()->getAddress(),
            'template.title' => $model->getTemplate()->getTitle(),
            'template.payment_terms' => $model->getTemplate()->getPaymentTerms(),
            'template.due_days' => $model->getTemplate()->getDueDays(),
            'template.vat_id' => $model->getTemplate()->getVatId(),
            'template.contact' => $model->getTemplate()->getContact(),
            'template.payment_details' => $model->getTemplate()->getPaymentDetails(),

            'query.begin' => $this->getFormattedDateTime($model->getQuery()->getBegin()),
            'query.day' => $model->getQuery()->getBegin()->format('d'),
            'query.end' => $this->getFormattedDateTime($model->getQuery()->getEnd()),
            'query.month' => $this->getFormattedMonthName($model->getQuery()->getBegin()),
            'query.month_number' => $model->getQuery()->getBegin()->format('m'),
            'query.year' => $model->getQuery()->getBegin()->format('Y'),
        ];

        if (null !== $activity) {
            $values = array_merge($values, [
                'activity.id' => $activity->getId(),
                'activity.name' => $activity->getName(),
                'activity.comment' => $activity->getComment(),
                'activity.fixed_rate' => $activity->getFixedRate(),
                'activity.hourly_rate' => $activity->getHourlyRate(),
            ]);

            foreach ($activity->getVisibleMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'activity.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        if (null !== $project) {
            $values = array_merge($values, [
                'project.id' => $project->getId(),
                'project.name' => $project->getName(),
                'project.comment' => $project->getComment(),
                'project.order_number' => $project->getOrderNumber(),
                'project.order_date' => null !== $project->getOrderDate() ? $this->getFormattedDateTime($project->getOrderDate()) : '',
                'project.fixed_rate' => $project->getFixedRate(),
                'project.hourly_rate' => $project->getHourlyRate(),
            ]);

            foreach ($project->getVisibleMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'project.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        if (null !== $customer) {
            $values = array_merge($values, [
                'customer.id' => $customer->getId(),
                'customer.address' => $customer->getAddress(),
                'customer.name' => $customer->getName(),
                'customer.contact' => $customer->getContact(),
                'customer.company' => $customer->getCompany(),
                'customer.vat' => $customer->getVatId(),
                'customer.number' => $customer->getNumber(),
                'customer.country' => $customer->getCountry(),
                'customer.homepage' => $customer->getHomepage(),
                'customer.comment' => $customer->getComment(),
                'customer.fixed_rate' => $customer->getFixedRate(),
                'customer.hourly_rate' => $customer->getHourlyRate(),
            ]);

            foreach ($customer->getVisibleMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'customer.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        return $values;
    }

    /**
     * @deprecated since 1.3 - will be removed with 2.0
     */
    protected function timesheetToArray(InvoiceItem $invoiceItem): array
    {
        @trigger_error('timesheetToArray() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return $this->invoiceItemToArray($invoiceItem);
    }

    protected function invoiceItemToArray(InvoiceItem $invoiceItem): array
    {
        $rate = $invoiceItem->getRate();
        $hourlyRate = $invoiceItem->getHourlyRate();
        $amount = $this->getFormattedDuration($invoiceItem->getDuration());
        $description = $invoiceItem->getDescription();

        if ($invoiceItem->isFixedRate()) {
            $hourlyRate = $invoiceItem->getFixedRate();
            $amount = $invoiceItem->getAmount();
        }

        if (empty($description)) {
            $description = $invoiceItem->getActivity()->getName();
        }

        $user = $invoiceItem->getUser();

        // this should never happen!
        if (empty($hourlyRate)) {
            $hourlyRate = 0;
        }

        $activity = $invoiceItem->getActivity();
        $project = $invoiceItem->getProject();
        $customer = $project->getCustomer();
        $currency = $customer->getCurrency();

        $begin = $invoiceItem->getBegin();
        $end = $invoiceItem->getEnd();

        $values = [
            'entry.row' => '',
            'entry.description' => $description,
            'entry.amount' => $amount,
            'entry.rate' => $this->getFormattedMoney($hourlyRate, $currency),
            'entry.rate_nc' => $this->getFormattedMoney($hourlyRate, null),
            'entry.total' => $this->getFormattedMoney($rate, $currency),
            'entry.total_nc' => $this->getFormattedMoney($rate, null),
            'entry.currency' => $currency,
            'entry.duration' => $invoiceItem->getDuration(),
            'entry.duration_decimal' => $this->getFormattedDecimalDuration($invoiceItem->getDuration()),
            'entry.duration_minutes' => number_format($invoiceItem->getDuration() / 60),
            'entry.begin' => $this->getFormattedDateTime($begin),
            'entry.begin_time' => $this->getFormattedTime($begin),
            'entry.begin_timestamp' => $begin->getTimestamp(),
            'entry.end' => $this->getFormattedDateTime($end),
            'entry.end_time' => $this->getFormattedTime($end),
            'entry.end_timestamp' => $end->getTimestamp(),
            'entry.date' => $this->getFormattedDateTime($begin),
            'entry.user_id' => $user->getId(),
            'entry.user_name' => $user->getUsername(),
            'entry.user_title' => $user->getTitle(),
            'entry.user_alias' => $user->getAlias(),
            'entry.activity' => $activity->getName(),
            'entry.activity_id' => $activity->getId(),
            'entry.project' => $project->getName(),
            'entry.project_id' => $project->getId(),
            'entry.customer' => $customer->getName(),
            'entry.customer_id' => $customer->getId(),
        ];

        foreach ($invoiceItem->getAdditionalFields() as $name => $value) {
            $values = array_merge($values, [
                'entry.meta.' . $name => $value,
            ]);
        }

        return $values;
    }

    /**
     * @param mixed $file
     * @param string $filename
     * @return BinaryFileResponse
     */
    protected function getFileResponse($file, $filename)
    {
        $response = new BinaryFileResponse($file);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', $this->getContentType());
        $response->headers->set('Content-Disposition', $disposition);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
