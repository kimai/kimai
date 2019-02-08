<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use App\Twig\DateExtensions;
use App\Twig\Extensions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractSpreadsheetRenderer
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
     * @param TranslatorInterface $translator
     * @param DateExtensions $dateExtension
     * @param Extensions $extensions
     */
    public function __construct(TranslatorInterface $translator, DateExtensions $dateExtension, Extensions $extensions)
    {
        $this->translator = $translator;
        $this->dateExtension = $dateExtension;
        $this->extension = $extensions;
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedDateTime(\DateTime $date)
    {
        return $this->dateExtension->dateShort($date) . ' ' . $this->dateExtension->time($date);
    }

    /**
     * @param $amount
     * @return mixed
     */
    protected function getFormattedMoney($amount, $currency)
    {
        return $this->extension->money($amount, $currency);
    }

    /**
     * @param Timesheet $timesheet
     * @return string
     */
    protected function getUsername(Timesheet $timesheet)
    {
        if (!empty($timesheet->getUser()->getAlias())) {
            return $timesheet->getUser()->getAlias();
        }

        return $timesheet->getUser()->getUsername();
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
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function fromArrayToSpreadsheet(array $timesheets, TimesheetQuery $query): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.begin'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.end'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.user'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.customer'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.project'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.activity'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.description'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.exported'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.hourly_rate'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.fixed_rate'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.duration'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.rate'));

        $entryHeaderRow = $recordsHeaderRow + 1;

        $durationTotal = 0;
        $currency = false;
        $rateTotal = 0;

        foreach ($timesheets as $timesheet) {
            $entryHeaderColumn = 1;

            $durationTotal += $timesheet->getDuration();
            $rateTotal += $timesheet->getRate();
            if ($currency === false) {
                $currency = $timesheet->getProject()->getCustomer()->getCurrency();
            }
            if ($currency !== $timesheet->getProject()->getCustomer()->getCurrency()) {
                $currency = null;
            }

            $customerCurrency = $timesheet->getProject()->getCustomer()->getCurrency();

            $exported = $timesheet->isExported() ? 'entryState.exported' : 'entryState.not_exported';

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getFormattedDateTime($timesheet->getBegin()));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getFormattedDateTime($timesheet->getEnd()));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getUsername($timesheet));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $timesheet->getProject()->getCustomer()->getName());
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $timesheet->getProject()->getName());
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $timesheet->getActivity()->getName());
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $timesheet->getDescription());
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->translator->trans($exported));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getFormattedMoney($timesheet->getHourlyRate(), $customerCurrency));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getFormattedMoney($timesheet->getFixedRate(), $customerCurrency));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getFormattedDuration($timesheet->getDuration()));
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $this->getFormattedMoney($timesheet->getRate(), $customerCurrency));

            $entryHeaderRow++;
        }

        $sheet->setCellValueByColumnAndRow(11, $entryHeaderRow, $this->getFormattedDuration($durationTotal));
        $sheet->setCellValueByColumnAndRow(12, $entryHeaderRow, $this->getFormattedMoney($rateTotal, $currency));
        $sheet->getCellByColumnAndRow(11, $entryHeaderRow)->getStyle()->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getCellByColumnAndRow(11, $entryHeaderRow)->getStyle()->getFont()->setBold(true);

        $sheet->getCellByColumnAndRow(12, $entryHeaderRow)->getStyle()->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getCellByColumnAndRow(12, $entryHeaderRow)->getStyle()->getFont()->setBold(true);

        return $spreadsheet;
    }

    /**
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        $spreadsheet = $this->fromArrayToSpreadsheet($timesheets, $query);
        $filename = $this->saveSpreadsheet($spreadsheet);

        return $this->getFileResponse($filename, 'kimai-export' . $this->getFileExtension());
    }

    /**
     * @return string
     */
    abstract public function getFileExtension(): string;

    /**
     * @param mixed $file
     * @param string $filename
     * @return BinaryFileResponse
     */
    protected function getFileResponse($file, $filename): Response
    {
        $response = new BinaryFileResponse($file);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', $this->getContentType());
        $response->headers->set('Content-Disposition', $disposition);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @return string
     */
    abstract protected function getContentType(): string;

    /**
     * @param Spreadsheet $spreadsheet
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    abstract protected function saveSpreadsheet(Spreadsheet $spreadsheet): string;
}
