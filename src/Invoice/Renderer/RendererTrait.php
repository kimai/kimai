<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Entity\Timesheet;
use App\Entity\UserPreference;
use App\Model\InvoiceModel;
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
     * @param $amount
     * @return mixed
     */
    abstract protected function getFormattedMoney($amount, $currency);

    /**
     * @param \DateTime $date
     * @return mixed
     */
    abstract protected function getFormattedMonthName(\DateTime $date);

    /**
     * @param $seconds
     * @return mixed
     */
    abstract protected function getFormattedDuration($seconds);

    /**
     * @param InvoiceModel $model
     * @return array
     */
    protected function modelToReplacer(InvoiceModel $model)
    {
        $customer = $model->getCustomer();
        $project = $model->getQuery()->getProject();
        $currency = $model->getCalculator()->getCurrency();

        $values = [
            'invoice.due_date' => $this->getFormattedDateTime($model->getDueDate()),
            'invoice.date' => $this->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.number' => $model->getNumberGenerator()->getInvoiceNumber(),
            'invoice.currency' => $model->getCalculator()->getCurrency(),
            'invoice.vat' => $model->getCalculator()->getVat(),
            'invoice.tax' => $this->getFormattedMoney($model->getCalculator()->getTax(), $currency),
            'invoice.total_time' => $this->getFormattedDuration($model->getCalculator()->getTimeWorked()),
            'invoice.total' => $this->getFormattedMoney($model->getCalculator()->getTotal(), $currency),
            'invoice.subtotal' => $this->getFormattedMoney($model->getCalculator()->getSubtotal(), $currency),

            'template.name' => $model->getTemplate()->getName(),
            'template.company' => $model->getTemplate()->getCompany(),
            'template.address' => $model->getTemplate()->getAddress(),
            'template.title' => $model->getTemplate()->getTitle(),
            'template.payment_terms' => $model->getTemplate()->getPaymentTerms(),
            'template.due_days' => $model->getTemplate()->getDueDays(),

            'query.begin' => $this->getFormattedDateTime($model->getQuery()->getBegin()),
            'query.end' => $this->getFormattedDateTime($model->getQuery()->getEnd()),
            'query.month' => $this->getFormattedMonthName($model->getQuery()->getBegin()),
            'query.year' => $model->getQuery()->getBegin()->format('Y'),
        ];

        if (null !== $project) {
            $values = array_merge($values, [
                'project.id' => $project->getId(),
                'project.name' => $project->getName(),
                'project.comment' => $project->getComment(),
                'project.order_number' => $project->getOrderNumber(),
            ]);
        }

        if (null !== $customer) {
            $values = array_merge($values, [
                'customer.id' => $customer->getId(),
                'customer.address' => $customer->getAddress(),
                'customer.name' => $customer->getName(),
                'customer.contact' => $customer->getContact(),
                'customer.company' => $customer->getCompany(),
                'customer.number' => $customer->getNumber(),
                'customer.country' => $customer->getCountry(),
                'customer.homepage' => $customer->getHomepage(),
                'customer.comment' => $customer->getComment(),
            ]);
        }

        return $values;
    }

    /**
     * @param Timesheet $timesheet
     * @return array
     */
    protected function timesheetToArray(Timesheet $timesheet)
    {
        $rate = $timesheet->getRate();
        $hourlyRate = $timesheet->getHourlyRate();
        $amount = $this->getFormattedDuration($timesheet->getDuration());
        $description = $timesheet->getDescription();

        if (null !== $timesheet->getFixedRate()) {
            $rate = $timesheet->getFixedRate();
            $hourlyRate = $timesheet->getFixedRate();
            $amount = 1;
        }

        if (empty($description)) {
            $description = $timesheet->getActivity()->getName();
        }

        $user = $timesheet->getUser();

        if (empty($hourlyRate)) {
            $hourlyRate = $user->getPreferenceValue(UserPreference::HOURLY_RATE);
        }

        $activity = $timesheet->getActivity();
        $project = $timesheet->getProject();
        $customer = $project->getCustomer();
        $currency = $customer->getCurrency();

        $begin = $timesheet->getBegin();
        $end = $timesheet->getEnd();

        return [
            'entry.row' => '',
            'entry.description' => $description,
            'entry.amount' => $amount,
            'entry.rate' => $this->getFormattedMoney($hourlyRate, $currency),
            'entry.total' => $this->getFormattedMoney($rate, $currency),
            'entry.currency' => $currency,
            'entry.duration' => $timesheet->getDuration(),
            'entry.duration_minutes' => number_format($timesheet->getDuration() / 60),
            'entry.begin' => $this->getFormattedDateTime($begin),
            'entry.begin_time' => date('H:i', $begin->getTimestamp()),
            'entry.begin_timestamp' => $begin->getTimestamp(),
            'entry.end' => $this->getFormattedDateTime($end),
            'entry.end_time' => date('H:i', $end->getTimestamp()),
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
