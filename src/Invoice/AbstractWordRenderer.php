<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\InvoiceDocument;
use App\Entity\Timesheet;
use App\Entity\UserPreference;
use App\Model\InvoiceModel;
use App\Twig\DateExtensions;
use App\Twig\Extensions;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractWordRenderer
{
    /**
     * @var DateExtensions
     */
    protected $dateExtension;

    /**
     * @var Extensions
     */
    protected $extension;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param DateExtensions $dateExtension
     */
    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator, DateExtensions $dateExtension, Extensions $extensions)
    {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->dateExtension = $dateExtension;
        $this->extension = $extensions;
    }

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
    protected function getFormattedDateTime(\DateTime $date)
    {
        return $this->dateExtension->dateShort($date);
    }

    /**
     * @param $amount
     * @return mixed
     */
    protected function getFormattedMoney($amount)
    {
        return $this->extension->money($amount);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedMonthName(\DateTime $date)
    {
        return $this->translator->trans($this->dateExtension->monthName($date));
    }

    /**
     * @param $seconds
     * @return mixed
     */
    protected function getFormattedDuration($seconds)
    {
        return $this->extension->duration($seconds);
    }

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
     * @param string $fallbackRate
     * @return array
     */
    protected function timesheetToArray(Timesheet $timesheet, $fallbackRate)
    {
        $rate = $timesheet->getRate();
        $hourlyRate = $timesheet->getHourlyRate();
        $amount = $this->extension->duration($timesheet->getDuration());
        $description = $timesheet->getDescription();

        if (null !== $timesheet->getFixedRate()) {
            $rate = $timesheet->getFixedRate();
            $hourlyRate = $timesheet->getFixedRate();
            $amount = 1;
        }

        if (empty($description)) {
            $description = $timesheet->getActivity()->getName();
        }

        if (empty($hourlyRate)) {
            $hourlyRate = $fallbackRate;
        }

        return [
            'entry.description' => $description,
            'entry.amount' => $amount,
            'entry.rate' => $this->getFormattedMoney($hourlyRate),
            'entry.total' => $this->getFormattedMoney($rate),
            'entry.date' => $this->getFormattedDateTime($timesheet->getBegin()),
        ];
    }

    /**
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $filename = basename($document->getFilename());

        $template = new TemplateProcessor($document->getFilename());
        foreach($this->modelToReplacer($model) as $key => $value) {
            $template->setValue($key, $value);
        }

        $template->cloneRow('entry.description', count($model->getCalculator()->getEntries()));
        $i = 1;
        $rate = $model->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE);
        foreach($model->getCalculator()->getEntries() as $entry) {
            $values = $this->timesheetToArray($entry, $rate);
            foreach($values as $search => $replace) {
                $template->setValue($search . '#' . $i, $replace);
            }
            $i++;
        }

        $cacheFile = $template->save();

        clearstatcache(true, $cacheFile);

        $response = new BinaryFileResponse(new Stream($cacheFile));
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', $this->getContentType());
        $response->headers->set('Content-Disposition', $disposition);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
