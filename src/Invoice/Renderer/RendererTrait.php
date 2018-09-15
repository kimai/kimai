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
    abstract protected function getFormattedMoney($amount);

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
        return [
            'invoice.due_date' => $this->getFormattedDateTime($model->getDueDate()),
            'invoice.date' => $this->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.number' => $model->getNumberGenerator()->getInvoiceNumber(),
            'invoice.currency' => $model->getCalculator()->getCurrency(),
            'invoice.vat' => $model->getCalculator()->getVat(),
            'invoice.tax' => $this->getFormattedMoney($model->getCalculator()->getTax()),
            'invoice.total_time' => $this->getFormattedDuration($model->getCalculator()->getTimeWorked()),
            'invoice.total' => $this->getFormattedMoney($model->getCalculator()->getTotal()),
            'invoice.subtotal' => $this->getFormattedMoney($model->getCalculator()->getSubtotal()),

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

            'customer.address' => $model->getCustomer()->getAddress(),
            'customer.name' => $model->getCustomer()->getName(),
            'customer.contact' => $model->getCustomer()->getContact(),
            'customer.company' => $model->getCustomer()->getCompany(),
            'customer.number' => $model->getCustomer()->getNumber(),
            'customer.country' => $model->getCustomer()->getCountry(),
            'customer.homepage' => $model->getCustomer()->getHomepage(),
            'customer.comment' => $model->getCustomer()->getComment(),
        ];
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
        $project = $activity->getProject();
        $customer = $project->getCustomer();

        return [
            'entry.description' => $description,
            'entry.amount' => $amount,
            'entry.rate' => $this->getFormattedMoney($hourlyRate),
            'entry.total' => $this->getFormattedMoney($rate),
            'entry.duration' => $timesheet->getDuration(),
            'entry.begin' => $this->getFormattedDateTime($timesheet->getBegin()),
            'entry.begin_timestamp' => $timesheet->getBegin()->getTimestamp(),
            'entry.end' => $this->getFormattedDateTime($timesheet->getEnd()),
            'entry.end_timestamp' => $timesheet->getEnd()->getTimestamp(),
            'entry.date' => $this->getFormattedDateTime($timesheet->getBegin()),
            'entry.user_id' => $user->getId(),
            'entry.user_name' => $user->getUsername(),
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
