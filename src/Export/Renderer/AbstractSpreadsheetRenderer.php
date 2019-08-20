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
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractSpreadsheetRenderer
{
    public const DATETIME_FORMAT = 'yyyy-mm-dd hh:mm';

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
    public function __construct(
        TranslatorInterface $translator,
        DateExtensions $dateExtension,
        Extensions $extensions
    ) {
        $this->translator = $translator;
        $this->dateExtension = $dateExtension;
        $this->extension = $extensions;
    }

    protected function setFormattedDateTime(Worksheet $sheet, $column, $row, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        $sheet->setCellValueByColumnAndRow($column, $row, Date::PHPToExcel($date));
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode(self::DATETIME_FORMAT);
    }

    protected function setFormattedDate(Worksheet $sheet, $column, $row, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        $sheet->setCellValueByColumnAndRow($column, $row, Date::PHPToExcel($date));
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD2);
    }

    protected function setDurationTotalFormula(Worksheet $sheet, $column, $row, $startCoordinate, $endCoordinate, $durationTotal)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=SUM(%s:%s) / 86400', $startCoordinate, $endCoordinate));
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode('[h]:mm');
    }

    /**
     * @param int $amount
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
     * @param int $seconds
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
        $publicMetaFields = [];
        foreach ($timesheets as $timesheet) {
            foreach ($timesheet->getVisibleMetaFields() as $metaField) {
                $publicMetaFields[] = $metaField->getName();
            }
        }

        $publicMetaFields = array_unique($publicMetaFields);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.date'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.begin'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.end'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.duration'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.user'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.customer'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.project'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.activity'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.description'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.exported'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.tags'));
        foreach ($publicMetaFields as $metaFieldName) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($metaFieldName));
        }
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.hourlyRate'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.fixedRate'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.duration'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn, $recordsHeaderRow, $this->translator->trans('label.rate'));

        $entryHeaderRow = $recordsHeaderRow + 1;

        $durationSecondsColumn = null;
        $durationTotal = 0;
        $currency = false;
        $rateTotal = 0;
        $dateTimeFormat = 'yyyy-mm-dd hh:mm';

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

            $this->setFormattedDate($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getBegin());
            $entryHeaderColumn++;

            $this->setFormattedDateTime($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getBegin());
            $entryHeaderColumn++;

            $this->setFormattedDateTime($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getEnd());
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $timesheet->getDuration());
            $durationSecondsColumn = $entryHeaderColumn;
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $this->getUsername($timesheet));
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $timesheet->getProject()->getCustomer()->getName());
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $timesheet->getProject()->getName());
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $timesheet->getActivity()->getName());
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $timesheet->getDescription());
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $this->translator->trans($exported));
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, implode(',', $timesheet->getTagsAsArray()));
            $entryHeaderColumn++;

            foreach ($publicMetaFields as $metaFieldName) {
                $metaField = $timesheet->getMetaField($metaFieldName);
                $metaFieldValue = '';
                if (null !== $metaField && $metaField->isVisible()) {
                    $metaFieldValue = $metaField->getValue();
                }
                $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $metaFieldValue);
            }

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $this->getFormattedMoney($timesheet->getHourlyRate(), $customerCurrency));
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $this->getFormattedMoney($timesheet->getFixedRate(), $customerCurrency));
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $this->getFormattedDuration($timesheet->getDuration()));
            $entryHeaderColumn++;

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $this->getFormattedMoney($timesheet->getRate(), $customerCurrency));
            $entryHeaderColumn++;

            $entryHeaderRow++;
        }

        $cellDurationTotal = $recordsHeaderColumn - 1;
        $cellRateTotal = $recordsHeaderColumn;

        $startCoordinate = $sheet->getCellByColumnAndRow($durationSecondsColumn, 2)->getCoordinate();
        $endCoordinate = $sheet->getCellByColumnAndRow($durationSecondsColumn, $entryHeaderRow - 1)->getCoordinate();
        $this->setDurationTotalFormula($sheet, $durationSecondsColumn, $entryHeaderRow, $startCoordinate, $endCoordinate, $durationTotal);

        $sheet->setCellValueByColumnAndRow($cellDurationTotal, $entryHeaderRow, $this->getFormattedDuration($durationTotal));
        $sheet->setCellValueByColumnAndRow($cellRateTotal, $entryHeaderRow, $this->getFormattedMoney($rateTotal, $currency));
        $sheet->getCellByColumnAndRow($cellDurationTotal, $entryHeaderRow)->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getCellByColumnAndRow($cellDurationTotal, $entryHeaderRow)->getStyle()->getFont()->setBold(true);

        $sheet->getCellByColumnAndRow($cellRateTotal, $entryHeaderRow)->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getCellByColumnAndRow($cellRateTotal, $entryHeaderRow)->getStyle()->getFont()->setBold(true);

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
     * @throws \Exception
     */
    abstract protected function saveSpreadsheet(Spreadsheet $spreadsheet): string;
}
