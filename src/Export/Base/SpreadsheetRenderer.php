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
use App\Entity\User;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\Package\CellFormatter\ArrayFormatter;
use App\Export\Package\CellFormatter\BooleanFormatter;
use App\Export\Package\CellFormatter\CellFormatterInterface;
use App\Export\Package\CellFormatter\DateFormatter;
use App\Export\Package\CellFormatter\DefaultFormatter;
use App\Export\Package\CellFormatter\DurationDecimalFormatter;
use App\Export\Package\CellFormatter\DurationFormatter;
use App\Export\Package\CellFormatter\RateFormatter;
use App\Export\Package\CellFormatter\TextFormatter;
use App\Export\Package\CellFormatter\TimeFormatter;
use App\Export\Package\Column;
use App\Export\Package\ColumnWidth;
use App\Export\Package\SpreadsheetPackage;
use App\Export\Template;
use App\Export\TemplateInterface;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal means no BC promise whatsoever!
 */
final class SpreadsheetRenderer
{
    /**
     * @var array<string, CellFormatterInterface>
     */
    private array $formatter = [];

    private ?TemplateInterface $template = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $voter,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function setTemplate(?TemplateInterface $template): void
    {
        $this->template = $template;
    }

    public function getTemplate(?TimesheetQuery $query = null): TemplateInterface
    {
        if ($this->template === null) {
            $template = new Template('default', 'default');
            $template->setColumns($this->getDefaultColumns($query));
            $template->setLocale('en');

            $this->template = $template;
        }

        return $this->template;
    }

    private function isRenderRate(TimesheetQuery $query): bool
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

    /**
     * @return MetaTableTypeInterface[]
     */
    private function findMetaColumns(MetaDisplayEventInterface $event): array
    {
        $this->eventDispatcher->dispatch($event);

        return $event->getFields();
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function writeSpreadsheet(SpreadsheetPackage $spreadsheetPackage, array $exportItems, TimesheetQuery $query): void
    {
        $columns = $this->getColumns($query);
        $spreadsheetPackage->setColumns($columns);

        $currentRow = 1;
        foreach ($exportItems as $exportItem) {
            $cells = [];
            foreach ($columns as $column) {
                $cells[] = $column->getValue($exportItem);
            }
            $spreadsheetPackage->addRow($cells);
            $currentRow++;
        }

        if ($currentRow > 1) {
            $totalColumns = ['duration', 'rate', 'internalRate'];
            // that should be enough for the near future: the number of array entries must cover the max number of columns
            $columnNames = [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
                'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
            ];
            $totalRow = [];
            $totalColumn = 1;
            foreach ($columns as $column) {
                $formula = null;
                if (\in_array($column->getName(), $totalColumns)) {
                    $columnName = $columnNames[$totalColumn - 1];
                    $formula = \sprintf('=SUBTOTAL(9,%s2:%s%s)', $columnName, $columnName, $currentRow);
                }
                $totalRow[] = $formula;
                $totalColumn++;
            }

            $spreadsheetPackage->addRow($totalRow, ['totals' => true]);
        }

        $spreadsheetPackage->save();
    }

    public function registerFormatter(string $name, CellFormatterInterface $cellFormatter): void
    {
        $this->formatter[$name] = $cellFormatter;
    }

    private function getFormatter(string $name): CellFormatterInterface
    {
        if (\array_key_exists($name, $this->formatter)) {
            return $this->formatter[$name];
        }

        return match ($name) {
            'date' => new DateFormatter(),
            'time' => new TimeFormatter(),
            'duration' => new DurationFormatter('[hh]:mm'),
            'duration_decimal' => new DurationDecimalFormatter(),
            'duration_seconds' => new DurationFormatter('[hh]:mm:ss'),
            default => new DefaultFormatter()
        };
    }

    /**
     * @return array<Column>
     */
    private function getColumns(TimesheetQuery $query): array
    {
        $showRates = $this->isRenderRate($query);

        $timesheetMeta = [];
        foreach ($this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $timesheetMeta['timesheet.meta.' . $metaField->getName()] = (new Column('timesheet.meta.' . $metaField->getName(), $this->getFormatter('default')))
                    ->withHeader($metaField->getLabel())
                    ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                        return $exportableItem->getMetaField($metaField->getName())?->getValue();
                    });
            }
        }

        $customerMeta = [];
        foreach ($this->findMetaColumns(new CustomerMetaDisplayEvent($query->copyTo(new CustomerQuery()), CustomerMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $customerMeta['customer.meta.' . $metaField->getName()] = (new Column('customer.meta.' . $metaField->getName(), $this->getFormatter('default')))
                    ->withHeader($metaField->getLabel())
                    ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                        return $exportableItem->getProject()?->getCustomer()?->getMetaField($metaField->getName())?->getValue();
                    });
            }
        }

        $projectMeta = [];
        foreach ($this->findMetaColumns(new ProjectMetaDisplayEvent($query->copyTo(new ProjectQuery()), ProjectMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $projectMeta['project.meta.' . $metaField->getName()] = (new Column('project.meta.' . $metaField->getName(), $this->getFormatter('default')))
                    ->withHeader($metaField->getLabel())
                    ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                        return $exportableItem->getProject()?->getMetaField($metaField->getName())?->getValue();
                    });
            }
        }

        $activityMeta = [];
        foreach ($this->findMetaColumns(new ActivityMetaDisplayEvent($query->copyTo(new ActivityQuery()), ActivityMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $activityMeta['activity.meta.' . $metaField->getName()] = (new Column('activity.meta.' . $metaField->getName(), $this->getFormatter('default')))
                    ->withHeader($metaField->getLabel())
                    ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                        return $exportableItem->getActivity()?->getMetaField($metaField->getName())?->getValue();
                    });
            }
        }

        $userMeta = [];
        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->eventDispatcher->dispatch($event);
        foreach ($event->getPreferences() as $metaField) {
            if ($metaField->getName() !== null) {
                $userMeta['user.meta.' . $metaField->getName()] = (new Column('user.meta.' . $metaField->getName(), $this->getFormatter('default')))
                    ->withHeader($metaField->getLabel())
                    ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                        return $exportableItem->getUser()?->getPreference($metaField->getName())?->getValue();
                    });
            }
        }

        $template = $this->getTemplate($query);

        $columns = [];

        $rateColumns = ['currency', 'rate', 'internal_rate', 'hourly_rate', 'fixed_rate'];

        foreach ($template->getColumns() as $column) {
            if ($column === 'date') {
                $columns[] = (new Column('date', $this->getFormatter('date')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getBegin());
            } elseif ($column === 'begin') {
                $columns[] = (new Column('begin', $this->getFormatter('time')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getBegin())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'end') {
                $columns[] = (new Column('end', $this->getFormatter('time')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getEnd())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'duration') {
                $columns[] = (new Column('duration', $this->getFormatter('duration')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getDuration())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'duration_decimal') {
                $columns[] = (new Column('duration', $this->getFormatter('duration_decimal')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getDuration())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'duration_seconds') {
                $columns[] = (new Column('duration', $this->getFormatter('duration_seconds')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getDuration())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'break') {
                // TODO remove method_exists with 3.0
                $columns[] = (new Column('break', $this->getFormatter('duration')))->withExtractor(fn (ExportableItem $exportableItem) => method_exists($exportableItem, 'getBreak') ? $exportableItem->getBreak() : 0)->withColumnWidth(ColumnWidth::SMALL); // @phpstan-ignore function.alreadyNarrowedType
            } elseif ($column === 'break_decimal') {
                // TODO remove method_exists with 3.0
                $columns[] = (new Column('break', $this->getFormatter('duration_decimal')))->withExtractor(fn (ExportableItem $exportableItem) => method_exists($exportableItem, 'getBreak') ? $exportableItem->getBreak() : 0)->withColumnWidth(ColumnWidth::SMALL); // @phpstan-ignore function.alreadyNarrowedType
            } elseif ($column === 'break_seconds') {
                // TODO remove method_exists with 3.0
                $columns[] = (new Column('break', $this->getFormatter('duration_seconds')))->withExtractor(fn (ExportableItem $exportableItem) => method_exists($exportableItem, 'getBreak') ? $exportableItem->getBreak() : 0)->withColumnWidth(ColumnWidth::SMALL); // @phpstan-ignore function.alreadyNarrowedType
            } elseif ($column === 'currency' && $showRates) {
                $columns[] = (new Column('currency', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getCurrency())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'rate' && $showRates) {
                $columns[] = (new Column('rate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getRate());
            } elseif ($column === 'internal_rate' && $showRates) {
                $columns[] = (new Column('internalRate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getInternalRate());
            } elseif ($column === 'hourly_rate' && $showRates) {
                $columns[] = (new Column('hourlyRate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getHourlyRate());
            } elseif ($column === 'fixed_rate' && $showRates) {
                $columns[] = (new Column('fixedRate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getFixedRate());
            } elseif ($column === 'user.alias') {
                $columns[] = (new Column('alias', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getUser()?->getDisplayName())->withColumnWidth(ColumnWidth::MEDIUM);
            } elseif ($column === 'user.name') {
                $columns[] = (new Column('username', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getUser()?->getUserIdentifier())->withColumnWidth(ColumnWidth::MEDIUM);
            } elseif ($column === 'user.email') {
                $columns[] = (new Column('email', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getUser()?->getEmail())->withColumnWidth(ColumnWidth::MEDIUM);
            } elseif ($column === 'user.account_number') {
                $columns[] = (new Column('account_number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getUser()?->getAccountNumber());
            } elseif ($column === 'customer.name') {
                $columns[] = (new Column('customer', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getName())->withColumnWidth(ColumnWidth::MEDIUM);
            } elseif ($column === 'project.name') {
                $columns[] = (new Column('project', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getName())->withColumnWidth(ColumnWidth::MEDIUM);
            } elseif ($column === 'activity.name') {
                $columns[] = (new Column('activity', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getActivity()?->getName())->withColumnWidth(ColumnWidth::MEDIUM);
            } elseif ($column === 'description') {
                $columns[] = (new Column('description', new TextFormatter(true)))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getDescription())->withColumnWidth(ColumnWidth::LARGE);
            } elseif ($column === 'exported') {
                $columns[] = (new Column('exported', new BooleanFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->isExported());
            } elseif ($column === 'billable') {
                $columns[] = (new Column('billable', new BooleanFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->isBillable())->withColumnWidth(ColumnWidth::SMALL);
            } elseif ($column === 'tags') {
                $columns[] = (new Column('tags', new ArrayFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getTagsAsArray());
            } elseif ($column === 'type') {
                $columns[] = (new Column('type', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getType());
            } elseif ($column === 'category') {
                $columns[] = (new Column('category', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getCategory());
            } elseif ($column === 'customer.number') {
                $columns[] = (new Column('number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getNumber());
            } elseif ($column === 'project.number') {
                $columns[] = (new Column('project_number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getNumber());
            } elseif ($column === 'activity.number') {
                $columns[] = (new Column('activity_number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getActivity()?->getNumber());
            } elseif ($column === 'customer.vat_id') {
                $columns[] = (new Column('vat_id', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getVatId());
            } elseif ($column === 'project.order_number') {
                $columns[] = (new Column('orderNumber', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getOrderNumber());
            } elseif (str_starts_with($column, 'timesheet.meta.') && \array_key_exists($column, $timesheetMeta)) {
                $columns[] = $timesheetMeta[$column];
            } elseif (str_starts_with($column, 'customer.meta.') && \array_key_exists($column, $customerMeta)) {
                $columns[] = $customerMeta[$column];
            } elseif (str_starts_with($column, 'project.meta.') && \array_key_exists($column, $projectMeta)) {
                $columns[] = $projectMeta[$column];
            } elseif (str_starts_with($column, 'activity.meta.') && \array_key_exists($column, $activityMeta)) {
                $columns[] = $activityMeta[$column];
            } elseif (str_starts_with($column, 'user.meta.') && \array_key_exists($column, $userMeta)) {
                $columns[] = $userMeta[$column];
            } else {
                if ($this->logger !== null && ($showRates || !\in_array($column, $rateColumns, true))) {
                    $this->logger->warning(\sprintf('Unknown column "%s" used in exporter template "%s".', $column, $template->getTitle()));
                }
            }
        }

        return $columns;
    }

    /**
     * @return array<int, string>
     */
    private function getDefaultColumns(?TimesheetQuery $query = null): array
    {
        // @deprecated from 2.36 - will be removed with 3.0
        $durationFormatter = 'duration';
        if (($user = $this->voter->getUser()) instanceof User) {
            $durationFormatter = $user->isExportDecimal() ? 'duration_decimal' : 'duration';
        }

        $columns = [
            'date',
            'begin',
            'end',
            $durationFormatter,
            'currency',
            'rate',
            'internal_rate',
            'hourly_rate',
            'fixed_rate',
            'user.alias',
            'user.name',
            'user.email',
            'user.account_number',
            'customer.name',
            'project.name',
            'activity.name',
            'description',
            'billable',
            'tags',
            'type',
            'category',
            'customer.number',
            'project.number',
            'customer.vat_id',
            'project.order_number',
        ];

        foreach ($this->findMetaColumns(new TimesheetMetaDisplayEvent($query ?? new TimesheetQuery(), TimesheetMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns[] = 'timesheet.meta.' . $metaField->getName();
            }
        }

        foreach ($this->findMetaColumns(new CustomerMetaDisplayEvent(new CustomerQuery(), CustomerMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns[] = 'customer.meta.' . $metaField->getName();
            }
        }

        foreach ($this->findMetaColumns(new ProjectMetaDisplayEvent(new ProjectQuery(), ProjectMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns[] = 'project.meta.' . $metaField->getName();
            }
        }

        foreach ($this->findMetaColumns(new ActivityMetaDisplayEvent(new ActivityQuery(), ActivityMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns[] = 'activity.meta.' . $metaField->getName();
            }
        }

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->eventDispatcher->dispatch($event);
        foreach ($event->getPreferences() as $metaField) {
            if ($metaField->getName() !== null) {
                $columns[] = 'user.meta.' . $metaField->getName();
            }
        }

        return $columns;
    }
}
