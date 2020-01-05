<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\MetaTableTypeInterface;
use App\Entity\Timesheet;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\TimesheetQuery;
use App\Twig\DateExtensions;
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
abstract class AbstractSpreadsheetRenderer
{
    public const DATETIME_FORMAT = 'yyyy-mm-dd hh:mm';
    public const TIME_FORMAT = 'hh:mm';
    public const DURATION_FORMAT = '[hh]:mm';
    public const RATE_FORMAT_DEFAULT = '#.##0,00 [$%1$s];-#.##0,00 [$%1$s]';
    public const RATE_FORMAT_LEFT = '_("%1$s"* #,##0.00_);_("%1$s"* \(#,##0.00\);_("%1$s"* "-"??_);_(@_)';
    public const RATE_FORMAT = self::RATE_FORMAT_LEFT;

    /**
     * @var DateExtensions
     */
    protected $dateExtension;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $voter;

    public function __construct(TranslatorInterface $translator, DateExtensions $dateExtension, EventDispatcherInterface $dispatcher, AuthorizationCheckerInterface $voter)
    {
        $this->translator = $translator;
        $this->dateExtension = $dateExtension;
        $this->dispatcher = $dispatcher;
        $this->voter = $voter;
    }

    protected function isRenderRate(TimesheetQuery $query): bool
    {
        return true;
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

    protected function setFormattedTime(Worksheet $sheet, $column, $row, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        $sheet->setCellValueByColumnAndRow($column, $row, Date::PHPToExcel($date));
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode(self::TIME_FORMAT);
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

    protected function setDurationTotal(Worksheet $sheet, $column, $row, $startCoordinate, $endCoordinate)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=SUM(%s:%s)', $startCoordinate, $endCoordinate));
        $style = $sheet->getStyleByColumnAndRow($column, $row);
        $style->getNumberFormat()->setFormatCode(self::DURATION_FORMAT);
    }

    protected function setDuration(Worksheet $sheet, $column, $row, $duration)
    {
        if (null === $duration) {
            $duration = 0;
        }
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=%s/86400', $duration));
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode(self::DURATION_FORMAT);
    }

    protected function setRateTotal(Worksheet $sheet, $column, $row, $startCoordinate, $endCoordinate)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=SUM(%s:%s)', $startCoordinate, $endCoordinate));
    }

    protected function setRate(Worksheet $sheet, $column, $row, $rate, $currency)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, $rate);
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode(
            sprintf(self::RATE_FORMAT_LEFT, $currency)
        );
    }

    /**
     * @param MetaDisplayEventInterface $event
     * @return MetaTableTypeInterface[]
     */
    protected function findMetaColumns(MetaDisplayEventInterface $event): array
    {
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    /**
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function fromArrayToSpreadsheet(array $timesheets, TimesheetQuery $query): Spreadsheet
    {
        $customerQuery = $query->copyTo(new CustomerQuery());

        $timesheetMetaFields = $this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT));
        $customerMetaFields = $this->findMetaColumns(new CustomerMetaDisplayEvent($customerQuery, CustomerMetaDisplayEvent::EXPORT));
        $projectMetaFields = $this->findMetaColumns(new ProjectMetaDisplayEvent($query, ProjectMetaDisplayEvent::EXPORT));
        $activityMetaFields = $this->findMetaColumns(new ActivityMetaDisplayEvent($query, ActivityMetaDisplayEvent::EXPORT));

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->dispatcher->dispatch($event);
        $userPreferences = $event->getPreferences();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $showRates = $this->isRenderRate($query);
        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.date'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.begin'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.end'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.duration'));
        if ($showRates) {
            $sheet->setCellValueByColumnAndRow(
                $recordsHeaderColumn++,
                $recordsHeaderRow,
                $this->translator->trans('label.rate')
            );
        }
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.user'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.customer'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.project'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.activity'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.description'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.exported'));
        $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans('label.tags'));
        if ($showRates) {
            $sheet->setCellValueByColumnAndRow(
                $recordsHeaderColumn++,
                $recordsHeaderRow,
                $this->translator->trans('label.hourlyRate')
            );
            $sheet->setCellValueByColumnAndRow(
                $recordsHeaderColumn++,
                $recordsHeaderRow,
                $this->translator->trans('label.fixedRate')
            );
        }
        foreach ($timesheetMetaFields as $metaField) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($metaField->getLabel()));
        }
        foreach ($customerMetaFields as $metaField) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($metaField->getLabel()));
        }
        foreach ($projectMetaFields as $metaField) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($metaField->getLabel()));
        }
        foreach ($activityMetaFields as $metaField) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($metaField->getLabel()));
        }
        foreach ($userPreferences as $preference) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($preference->getLabel()));
        }

        $entryHeaderRow = $recordsHeaderRow + 1;

        $durationColumn = null;
        $rateColumn = null;

        foreach ($timesheets as $timesheet) {
            $entryHeaderColumn = 1;

            $customerCurrency = $timesheet->getProject()->getCustomer()->getCurrency();
            $exported = $timesheet->isExported() ? 'entryState.exported' : 'entryState.not_exported';

            $this->setFormattedDate($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getBegin());
            $entryHeaderColumn++;

            $this->setFormattedTime($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getBegin());
            $entryHeaderColumn++;

            $this->setFormattedTime($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getEnd());
            $entryHeaderColumn++;

            $this->setDuration($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getDuration());
            $durationColumn = $entryHeaderColumn;
            $entryHeaderColumn++;

            if ($showRates) {
                $this->setRate($sheet, $entryHeaderColumn, $entryHeaderRow, $timesheet->getRate(), $customerCurrency);
                $rateColumn = $entryHeaderColumn;
                $entryHeaderColumn++;
            }

            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $timesheet->getUser()->getDisplayName());
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

            if ($showRates) {
                $this->setRate(
                    $sheet,
                    $entryHeaderColumn,
                    $entryHeaderRow,
                    $timesheet->getHourlyRate(),
                    $customerCurrency
                );
                $entryHeaderColumn++;

                $this->setRate(
                    $sheet,
                    $entryHeaderColumn,
                    $entryHeaderRow,
                    $timesheet->getFixedRate(),
                    $customerCurrency
                );
                $entryHeaderColumn++;
            }

            foreach ($timesheetMetaFields as $metaField) {
                $metaField = $timesheet->getMetaField($metaField->getName());
                $metaFieldValue = '';
                if (null !== $metaField) {
                    $metaFieldValue = $metaField->getValue();
                }
                $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $metaFieldValue);
            }
            foreach ($customerMetaFields as $metaField) {
                $metaField = $timesheet->getProject()->getCustomer()->getMetaField($metaField->getName());
                $metaFieldValue = '';
                if (null !== $metaField) {
                    $metaFieldValue = $metaField->getValue();
                }
                $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $metaFieldValue);
            }
            foreach ($projectMetaFields as $metaField) {
                $metaField = $timesheet->getProject()->getMetaField($metaField->getName());
                $metaFieldValue = '';
                if (null !== $metaField) {
                    $metaFieldValue = $metaField->getValue();
                }
                $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $metaFieldValue);
            }
            foreach ($activityMetaFields as $metaField) {
                $metaField = $timesheet->getActivity()->getMetaField($metaField->getName());
                $metaFieldValue = '';
                if (null !== $metaField) {
                    $metaFieldValue = $metaField->getValue();
                }
                $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $metaFieldValue);
            }
            foreach ($userPreferences as $preference) {
                $metaField = $timesheet->getUser()->getPreference($preference->getName());
                $metaFieldValue = '';
                if (null !== $metaField) {
                    $metaFieldValue = $metaField->getValue();
                }
                $sheet->setCellValueByColumnAndRow($entryHeaderColumn++, $entryHeaderRow, $metaFieldValue);
            }

            $entryHeaderRow++;
        }

        if (null !== $durationColumn) {
            $startCoordinate = $sheet->getCellByColumnAndRow($durationColumn, 2)->getCoordinate();
            $endCoordinate = $sheet->getCellByColumnAndRow($durationColumn, $entryHeaderRow - 1)->getCoordinate();
            $this->setDurationTotal($sheet, $durationColumn, $entryHeaderRow, $startCoordinate, $endCoordinate);
            $style = $sheet->getStyleByColumnAndRow($durationColumn, $entryHeaderRow);
            $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $style->getFont()->setBold(true);
        }

        if (null !== $rateColumn) {
            $startCoordinate = $sheet->getCellByColumnAndRow($rateColumn, 2)->getCoordinate();
            $endCoordinate = $sheet->getCellByColumnAndRow($rateColumn, $entryHeaderRow - 1)->getCoordinate();
            $this->setRateTotal($sheet, $rateColumn, $entryHeaderRow, $startCoordinate, $endCoordinate);
            $style = $sheet->getStyleByColumnAndRow($rateColumn, $entryHeaderRow);
            $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $style->getFont()->setBold(true);
        }

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
