<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\ExportableItem;
use App\Entity\MetaTableTypeInterface;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\ExportFilename;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use App\Twig\LocaleFormatExtensions;
use App\Utils\StringHelper;
use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal means no BC promise whatsoever!
 */
abstract class AbstractSpreadsheetRenderer
{
    public const DATETIME_FORMAT = 'yyyy-mm-dd hh:mm';
    public const TIME_FORMAT = 'hh:mm';
    public const DURATION_FORMAT = '[hh]:mm';
    public const DURATION_DECIMAL = '#0.00';

    // https://support.microsoft.com/de-de/office/zahlenformatcodes-5026bbd6-04bc-48cd-bf33-80f18b4eae68
    // Part 1 = positive; Part 2 = negative; Part 3 = zero; Part 4 = Text
    public const RATE_FORMAT_DEFAULT = '#.##0,00 [$%1$s];-#.##0,00 [$%1$s]';
    public const RATE_FORMAT_LEFT = '_("%1$s"* #,##0.00_);_("%1$s"* -#,##0.00;_("%1$s"* "-"??_);_(@_)';
    public const RATE_FORMAT_RIGHT = '_(* "%1$s" #,##0.00_);_(* "%1$s" -#,##0.00;_(* "%1$s" "-"??_);_(@_)';

    /**
     * @internal used in html to excel exporter
     */
    public const RATE_FORMAT_NO_CURRENCY = '#,##0.00;-#,##0.00';

    /**
     * @see self:RATE_FORMAT_*
     */
    protected string $rateFormat = self::RATE_FORMAT_LEFT;
    protected string $durationFormat = self::DURATION_FORMAT;
    protected int $durationBase = 86400;
    /**
     * @var array<string, array>
     */
    protected array $columns = [
        'date' => [],
        'begin' => [],
        'end' => [],
        'duration' => [],
        'rate' => [],
        'rate_internal' => [
            'label' => 'internalRate', // different translation key
        ],
        'user' => [
            'label' => 'name'
        ],
        'username' => [],
        'accountNumber' => [
            'label' => 'account_number'
        ],
        'customer' => [],
        'project' => [],
        'activity' => [],
        'description' => [
            'maxWidth' => 50,
            'wrapText' => false,
            'sanitizeDDE' => true,
        ],
        'exported' => [],
        'billable' => [],
        'tags' => [],
        'hourlyRate' => [],
        'fixedRate' => [],
        'timesheet-meta' => [],
        'customer-meta' => [],
        'project-meta' => [],
        'activity-meta' => [],
        'user-meta' => [],
        'type' => [],
        'category' => [],
        'customer_number' => [],
        'customer_vat' => [],
        'order_number' => [],
    ];

    public function __construct(
        protected TranslatorInterface $translator,
        protected LocaleFormatExtensions $dateExtension,
        protected EventDispatcherInterface $dispatcher,
        protected Security $voter
    ) {
    }

    protected function isRenderRate(TimesheetQuery $query): bool
    {
        if ($this->voter->getUser() === null) {
            // for command line export
            return true;
        }

        if (null !== $query->getUser()) {
            return $this->voter->isGranted('view_rate_own_timesheet');
        }

        return $this->voter->isGranted('view_rate_other_timesheet');
    }

    protected function setFormattedDateTime(Worksheet $sheet, int $column, int $row, ?DateTime $date): void
    {
        if (null === $date) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), '');

            return;
        }

        $excelDate = Date::PHPToExcel($date);

        if ($excelDate === false) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $date);

            return;
        }

        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $excelDate);
        // TODO why is that format hardcoded and does not depend on the users locale?
        $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row))->getNumberFormat()->setFormatCode(self::DATETIME_FORMAT);
    }

    protected function setFormattedTime(Worksheet $sheet, int $column, int $row, ?DateTime $date): void
    {
        if (null === $date) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), '');

            return;
        }

        $excelDate = Date::PHPToExcel($date);

        if ($excelDate === false) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $date);

            return;
        }

        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $excelDate);
        $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row))->getNumberFormat()->setFormatCode(self::TIME_FORMAT);
    }

    protected function setFormattedDate(Worksheet $sheet, int $column, int $row, ?DateTime $date): void
    {
        if (null === $date) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), '');

            return;
        }

        $excelDate = Date::PHPToExcel($date);

        if ($excelDate === false) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $date);

            return;
        }

        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $excelDate);
        // TODO why is that format hardcoded and does not depend on the users locale?
        $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
    }

    protected function setDurationTotal(Worksheet $sheet, int $column, int $row, string $startCoordinate, string $endCoordinate): void
    {
        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), \sprintf('=SUBTOTAL(9,%s:%s)', $startCoordinate, $endCoordinate));
        $style = $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row));
        $style->getNumberFormat()->setFormatCode($this->durationFormat);
    }

    protected function setDuration(Worksheet $sheet, int $column, int $row, ?int $duration): void
    {
        if (null === $duration) {
            $duration = 0;
        }
        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), \sprintf('=%s/%s', $duration, $this->durationBase));
        $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row))->getNumberFormat()->setFormatCode($this->durationFormat);
    }

    protected function setRateTotal(Worksheet $sheet, int $column, int $row, string $startCoordinate, string $endCoordinate): void
    {
        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), \sprintf('=SUBTOTAL(9,%s:%s)', $startCoordinate, $endCoordinate));
    }

    protected function setRateStyle(Worksheet $sheet, int $column, int $row, ?string $currency): void
    {
        $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row))->getNumberFormat()->setFormatCode(
            \sprintf($this->rateFormat, $currency ?? '')
        );
    }

    protected function setRate(Worksheet $sheet, int $column, int $row, ?float $rate, ?string $currency): void
    {
        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $rate ?? 0.0);
        $this->setRateStyle($sheet, $column, $row, $currency);
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
     * @param ExportableItem[] $exportItems
     * @param TimesheetQuery $query
     * @param array<string, array<string, callable|int|float|false|null>> $columns
     * @return array<string, array<string, callable|int|float|false|null>>
     */
    protected function getColumns(array $exportItems, TimesheetQuery $query, array $columns): array
    {
        if (null !== $query->getCurrentUser() && $query->getCurrentUser()->isExportDecimal()) {
            $this->durationFormat = self::DURATION_DECIMAL;
            $this->durationBase = 3600;
        }

        $showRates = $this->isRenderRate($query);

        if (isset($columns['date']) && !isset($columns['date']['render'])) {
            $columns['date']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $this->setFormattedDate($sheet, $column, $row, $entity->getBegin());
            };
        }

        if (isset($columns['begin']) && !isset($columns['begin']['render'])) {
            $columns['begin']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $this->setFormattedTime($sheet, $column, $row, $entity->getBegin());
            };
        }

        if (isset($columns['end']) && !isset($columns['end']['render'])) {
            $columns['end']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $this->setFormattedTime($sheet, $column, $row, $entity->getEnd());
            };
        }

        if (isset($columns['duration']) && !isset($columns['duration']['render'])) {
            $columns['duration']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $this->setDuration($sheet, $column, $row, $entity->getDuration());
            };
        }

        if ($showRates && isset($columns['rate']) && !isset($columns['rate']['render'])) {
            $columns['rate']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $currency = '';
                if (null !== $entity->getProject()) {
                    $currency = $entity->getProject()->getCustomer()->getCurrency();
                }
                $this->setRate($sheet, $column, $row, $entity->getRate(), $currency);
            };
        }

        if ($showRates && isset($columns['rate_internal']) && !isset($columns['rate_internal']['render'])) {
            $columns['rate_internal']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $currency = '';
                if (null !== $entity->getProject()) {
                    $currency = $entity->getProject()->getCustomer()->getCurrency();
                }
                $this->setRate($sheet, $column, $row, $entity->getInternalRate(), $currency);
            };
        }

        if (isset($columns['user']) && !isset($columns['user']['render'])) {
            $columns['user']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $user = '';
                if (null !== $entity->getUser()) {
                    $user = $entity->getUser()->getDisplayName();
                }
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $user);
            };
        }

        if (isset($columns['username'])) {
            if (!isset($columns['username']['render'])) {
                $columns['username']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                    $username = '';
                    if (null !== $entity->getUser()) {
                        $username = $entity->getUser()->getUserIdentifier();
                    }
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $username);
                };
            }
        }

        if (isset($columns['accountNumber'])) {
            if (!isset($columns['accountNumber']['render'])) {
                $columns['accountNumber']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                    $accountNumber = '';
                    if (null !== $entity->getUser()) {
                        $accountNumber = $entity->getUser()->getAccountNumber();
                    }
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $accountNumber);
                };
            }
        }

        if (isset($columns['customer']) && !isset($columns['customer']['render'])) {
            $columns['customer']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $customer = '';
                if (null !== $entity->getProject()) {
                    $customer = $entity->getProject()->getCustomer()->getName();
                }
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $customer);
            };
        }

        if (isset($columns['project']) && !isset($columns['project']['render'])) {
            $columns['project']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $project = '';
                if (null !== $entity->getProject()) {
                    $project = $entity->getProject()->getName();
                }
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $project);
            };
        }

        if (isset($columns['activity']) && !isset($columns['activity']['render'])) {
            $columns['activity']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $activity = '';
                if (null !== $entity->getActivity()) {
                    $activity = $entity->getActivity()->getName();
                }
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $activity);
            };
        }

        if (isset($columns['description']) && !isset($columns['description']['render'])) {
            $maxWidth = \array_key_exists('maxWidth', $columns['description']) && is_numeric($columns['description']['maxWidth']) ? (int) $columns['description']['maxWidth'] : null;
            $wrapText = \array_key_exists('wrapText', $columns['description']) ? (bool) $columns['description']['wrapText'] : false;
            $sanitizeText = \array_key_exists('sanitizeDDE', $columns['description']) ? (bool) $columns['description']['sanitizeDDE'] : true;

            // This column has a column-only formatter to set the maximum width of a column.
            // It needs to be executed once, so we use this as a flag on when to skip it.
            $isColumnFormatted = false;

            $columns['description']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) use (&$isColumnFormatted, $maxWidth, $wrapText, $sanitizeText) {
                $cell = $sheet->getCell(CellAddress::fromColumnAndRow($column, $row));
                $desc = $entity->getDescription();

                if ($sanitizeText && null !== $desc) {
                    $desc = StringHelper::sanitizeDDE($desc);
                }

                $cell->setValueExplicit($desc, DataType::TYPE_STRING);

                // Apply wrap text if configured
                if ($wrapText) {
                    $cell->getStyle()->getAlignment()->setWrapText(true);
                }

                // Apply max width, only needs to be once per column
                if (!$isColumnFormatted) {
                    if (null !== $maxWidth) {
                        $sheet->getColumnDimensionByColumn($column)->setWidth($maxWidth);
                    }
                    $isColumnFormatted = true;
                }
            };
        }

        if (isset($columns['exported']) && !isset($columns['exported']['render'])) {
            $columns['exported']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $exported = $entity->isExported() ? 'yes' : 'no';
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $this->translator->trans($exported));
            };
        }

        if (isset($columns['billable']) && !isset($columns['billable']['render'])) {
            $columns['billable']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $exported = $entity->isBillable() ? 'yes' : 'no';
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $this->translator->trans($exported));
            };
        }

        if (isset($columns['tags']) && !isset($columns['tags']['render'])) {
            $columns['tags']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), implode(',', $entity->getTagsAsArray()));
            };
        }

        if ($showRates && isset($columns['hourlyRate']) && !isset($columns['hourlyRate']['render'])) {
            $columns['hourlyRate']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $currency = '';
                if (null !== $entity->getProject()) {
                    $currency = $entity->getProject()->getCustomer()->getCurrency();
                }
                $this->setRate($sheet, $column, $row, $entity->getHourlyRate(), $currency);
            };
        }

        if ($showRates && isset($columns['fixedRate']) && !isset($columns['fixedRate']['render'])) {
            $columns['fixedRate']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $currency = '';
                if (null !== $entity->getProject()) {
                    $currency = $entity->getProject()->getCustomer()->getCurrency();
                }
                $this->setRate($sheet, $column, $row, $entity->getFixedRate(), $currency);
            };
        }

        if (isset($columns['timesheet-meta'])) {
            $timesheetMetaFields = $this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT));

            $columns['timesheet-meta'] = [
                'header' => function (Worksheet $sheet, int $row, int $column) use ($timesheetMetaFields): int {
                    foreach ($timesheetMetaFields as $metaField) {
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $this->translator->trans($metaField->getLabel()));
                    }

                    return \count($timesheetMetaFields);
                },
                'render' => function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) use ($timesheetMetaFields): int {
                    foreach ($timesheetMetaFields as $metaField) {
                        $metaFieldValue = '';
                        $metaField = $entity->getMetaField($metaField->getName());
                        if (null !== $metaField) {
                            $metaFieldValue = $metaField->getValue();
                        }
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $metaFieldValue);
                    }

                    return \count($timesheetMetaFields);
                }
            ];
        }

        if (isset($columns['customer-meta'])) {
            $customerMetaFields = $this->findMetaColumns(new CustomerMetaDisplayEvent($query->copyTo(new CustomerQuery()), CustomerMetaDisplayEvent::EXPORT));

            $columns['customer-meta'] = [
                'header' => function (Worksheet $sheet, int $row, int $column) use ($customerMetaFields): int {
                    foreach ($customerMetaFields as $metaField) {
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $this->translator->trans($metaField->getLabel()));
                    }

                    return \count($customerMetaFields);
                },
                'render' => function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) use ($customerMetaFields): int {
                    foreach ($customerMetaFields as $metaField) {
                        $metaFieldValue = '';
                        if (null !== $entity->getProject()) {
                            $metaField = $entity->getProject()->getCustomer()->getMetaField($metaField->getName());
                            if (null !== $metaField) {
                                $metaFieldValue = $metaField->getValue();
                            }
                        }
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $metaFieldValue);
                    }

                    return \count($customerMetaFields);
                }
            ];
        }

        if (isset($columns['project-meta'])) {
            $projectMetaFields = $this->findMetaColumns(new ProjectMetaDisplayEvent($query->copyTo(new ProjectQuery()), ProjectMetaDisplayEvent::EXPORT));
            $columns['project-meta'] = [
                'header' => function (Worksheet $sheet, int $row, int $column) use ($projectMetaFields): int {
                    foreach ($projectMetaFields as $metaField) {
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $this->translator->trans($metaField->getLabel()));
                    }

                    return \count($projectMetaFields);
                },
                'render' => function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) use ($projectMetaFields): int {
                    foreach ($projectMetaFields as $metaField) {
                        $metaFieldValue = '';
                        if (null !== $entity->getProject()) {
                            $metaField = $entity->getProject()->getMetaField($metaField->getName());
                            if (null !== $metaField) {
                                $metaFieldValue = $metaField->getValue();
                            }
                        }
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $metaFieldValue);
                    }

                    return \count($projectMetaFields);
                }
            ];
        }

        if (isset($columns['activity-meta'])) {
            $activityMetaFields = $this->findMetaColumns(new ActivityMetaDisplayEvent($query->copyTo(new ActivityQuery()), ActivityMetaDisplayEvent::EXPORT));
            $columns['activity-meta'] = [
                'header' => function (Worksheet $sheet, int $row, int $column) use ($activityMetaFields): int {
                    foreach ($activityMetaFields as $metaField) {
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $this->translator->trans($metaField->getLabel()));
                    }

                    return \count($activityMetaFields);
                },
                'render' => function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) use ($activityMetaFields): int {
                    foreach ($activityMetaFields as $metaField) {
                        $metaFieldValue = '';
                        if (null !== $entity->getActivity()) {
                            $metaField = $entity->getActivity()->getMetaField($metaField->getName());
                            if (null !== $metaField) {
                                $metaFieldValue = $metaField->getValue();
                            }
                        }
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $metaFieldValue);
                    }

                    return \count($activityMetaFields);
                }
            ];
        }

        if (isset($columns['user-meta'])) {
            $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
            $this->dispatcher->dispatch($event);
            $userPreferences = $event->getPreferences();
            $columns['user-meta'] = [
                'header' => function (Worksheet $sheet, int $row, int $column) use ($userPreferences): int {
                    foreach ($userPreferences as $metaField) {
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $this->translator->trans($metaField->getLabel()));
                    }

                    return \count($userPreferences);
                },
                'render' => function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) use ($userPreferences): int {
                    foreach ($userPreferences as $preference) {
                        $metaFieldValue = '';
                        if (null !== $entity->getUser()) {
                            $metaField = $entity->getUser()->getPreference($preference->getName());
                            if (null !== $metaField) {
                                $metaFieldValue = $metaField->getValue();
                            }
                        }
                        $sheet->setCellValue(CellAddress::fromColumnAndRow($column++, $row), $metaFieldValue);
                    }

                    return \count($userPreferences);
                }
            ];
        }

        if (isset($columns['type']) && !isset($columns['type']['render'])) {
            $columns['type']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $entity->getType());
            };
        }

        if (isset($columns['category']) && !isset($columns['category']['render'])) {
            $columns['category']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $entity->getCategory());
            };
        }

        if (isset($columns['customer_number'])) {
            if (!isset($columns['customer_number']['header'])) {
                $columns['customer_number']['header'] = function (Worksheet $sheet, int $row, int $column): int {
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $this->translator->trans('number'));

                    return 1;
                };
            }

            if (!isset($columns['customer_number']['render'])) {
                $columns['customer_number']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                    $customerId = '';
                    if (null !== $entity->getProject()) {
                        $customerId = $entity->getProject()->getCustomer()->getNumber();
                    }
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $customerId);
                };
            }
        }

        if (isset($columns['customer_vat']) && !isset($columns['customer_vat']['render'])) {
            if (!isset($columns['customer_vat']['header'])) {
                $columns['customer_vat']['header'] = function (Worksheet $sheet, int $row, int $column): int {
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $this->translator->trans('vat_id'));

                    return 1;
                };
            }

            if (!isset($columns['customer_vat']['render'])) {
                $columns['customer_vat']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                    $customerVat = '';
                    if (null !== $entity->getProject()) {
                        $customerVat = $entity->getProject()->getCustomer()->getVatId();
                    }
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $customerVat);
                };
            }
        }

        if (isset($columns['order_number']) && !isset($columns['order_number']['render'])) {
            if (!isset($columns['order_number']['header'])) {
                $columns['order_number']['header'] = function (Worksheet $sheet, int $row, int $column): int {
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $this->translator->trans('orderNumber'));

                    return 1;
                };
            }

            if (!isset($columns['order_number']['render'])) {
                $columns['order_number']['render'] = function (Worksheet $sheet, int $row, int $column, ExportableItem $entity) {
                    $orderNumber = '';
                    if (null !== $entity->getProject()) {
                        $orderNumber = $entity->getProject()->getOrderNumber();
                    }
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), $orderNumber);
                };
            }
        }

        if (!$showRates) {
            $removes = ['rate', 'fixedRate', 'hourlyRate', 'rate_internal'];
            foreach ($removes as $removeMe) {
                if (\array_key_exists($removeMe, $columns)) {
                    unset($columns[$removeMe]);
                }
            }
        }

        return $columns;
    }

    /**
     * @param ExportableItem[] $exportItems
     * @param TimesheetQuery $query
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function fromArrayToSpreadsheet(array $exportItems, TimesheetQuery $query): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set default row height to automatic, so we can specify wrap text columns later on
        // without bloating the output file as we would need to store stylesheet info for every cell.
        // LibreOffice is still not considering this flag, @see https://github.com/PHPOffice/PHPExcel/issues/588
        // with no solution implemented so nothing we can do about it there.
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        $columns = $this->getColumns($exportItems, $query, $this->columns);

        foreach ($columns as $label => $settings) {
            if (isset($settings['header'])) {
                if (!\is_callable($settings['header'])) {
                    throw new \RuntimeException('Invalid header renderer given for: ' . $label);
                }
                $amount = $settings['header']($sheet, $recordsHeaderRow, $recordsHeaderColumn);
                $recordsHeaderColumn += $amount;
            } else {
                $sheet->setCellValue(CellAddress::fromColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow), $this->translator->trans((\array_key_exists('label', $settings) && \is_string($settings['label'])) ? $settings['label'] : $label));
            }
        }

        $entryHeaderRow = $recordsHeaderRow + 1;

        $durationColumn = null;
        $rateColumn = null;
        $internalRateColumn = null;

        foreach ($exportItems as $exportItem) {
            $entryHeaderColumn = 1;

            foreach ($columns as $label => $settings) {
                if ($label === 'duration') {
                    $durationColumn = $entryHeaderColumn;
                } elseif ($label === 'rate') {
                    $rateColumn = $entryHeaderColumn;
                } elseif ($label === 'rate_internal') {
                    $internalRateColumn = $entryHeaderColumn;
                }

                if (!\array_key_exists('render', $settings) || !\is_callable($settings['render'])) {
                    throw new \RuntimeException(\sprintf('Missing or invalid renderer for export column %s', $label));
                }

                $amount = $settings['render']($sheet, $entryHeaderRow, $entryHeaderColumn, $exportItem);
                $entryHeaderColumn += (null === $amount) ? 1 : (int) $amount;
            }

            $entryHeaderRow++;
        }

        if ($this->isTotalRowSupported()) {
            if (null !== $durationColumn) {
                $startCoordinate = $sheet->getCell(CellAddress::fromColumnAndRow($durationColumn, 2))->getCoordinate();
                $endCoordinate = $sheet->getCell(CellAddress::fromColumnAndRow($durationColumn, $entryHeaderRow - 1))->getCoordinate();
                $this->setDurationTotal($sheet, $durationColumn, $entryHeaderRow, $startCoordinate, $endCoordinate);
                $style = $sheet->getStyle(CellAddress::fromColumnAndRow($durationColumn, $entryHeaderRow));
                $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $style->getFont()->setBold(true);
            }

            if (null !== $rateColumn) {
                $startCoordinate = $sheet->getCell(CellAddress::fromColumnAndRow($rateColumn, 2))->getCoordinate();
                $endCoordinate = $sheet->getCell(CellAddress::fromColumnAndRow($rateColumn, $entryHeaderRow - 1))->getCoordinate();
                $this->setRateTotal($sheet, $rateColumn, $entryHeaderRow, $startCoordinate, $endCoordinate);
                $style = $sheet->getStyle(CellAddress::fromColumnAndRow($rateColumn, $entryHeaderRow));
                $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $style->getFont()->setBold(true);
            }

            if (null !== $internalRateColumn) {
                $startCoordinate = $sheet->getCell(CellAddress::fromColumnAndRow($internalRateColumn, 2))->getCoordinate();
                $endCoordinate = $sheet->getCell(CellAddress::fromColumnAndRow($internalRateColumn, $entryHeaderRow - 1))->getCoordinate();
                $this->setRateTotal($sheet, $internalRateColumn, $entryHeaderRow, $startCoordinate, $endCoordinate);
                $style = $sheet->getStyle(CellAddress::fromColumnAndRow($internalRateColumn, $entryHeaderRow));
                $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $style->getFont()->setBold(true);
            }
        }

        return $spreadsheet;
    }

    protected function isTotalRowSupported(): bool
    {
        return false;
    }

    /**
     * @param ExportableItem[] $exportItems
     * @param TimesheetQuery $query
     * @return Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        $spreadsheet = $this->fromArrayToSpreadsheet($exportItems, $query);
        $file = $this->saveSpreadsheet($spreadsheet);
        $filename = new ExportFilename($query);

        return $this->getFileResponse($file, $filename->getFilename() . $this->getFileExtension());
    }

    /**
     * @return string
     */
    abstract public function getFileExtension(): string;

    /**
     * @param string $file
     * @param string $filename
     * @return BinaryFileResponse
     */
    protected function getFileResponse(string $file, string $filename): BinaryFileResponse
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
