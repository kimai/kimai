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
use App\Export\Package\CellFormatter\ArrayFormatter;
use App\Export\Package\CellFormatter\BooleanFormatter;
use App\Export\Package\CellFormatter\CellFormatterInterface;
use App\Export\Package\CellFormatter\DateFormatter;
use App\Export\Package\CellFormatter\DefaultFormatter;
use App\Export\Package\CellFormatter\DurationFormatter;
use App\Export\Package\CellFormatter\RateFormatter;
use App\Export\Package\CellFormatter\TextFormatter;
use App\Export\Package\CellFormatter\TimeFormatter;
use App\Export\Package\Column;
use App\Export\Package\SpreadsheetPackage;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal means no BC promise whatsoever!
 */
final class SpreadsheetRenderer
{
    /**
     * @var array<string, CellFormatterInterface>
     */
    private array $formatter = [];

    public function __construct(
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $dispatcher,
        protected Security $voter
    ) {
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
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function writeSpreadsheet(SpreadsheetPackage $spreadsheetPackage, array $exportItems, TimesheetQuery $query): void
    {
        $columns = $this->getColumns($query);

        $headerRow = [];
        foreach ($columns as $column) {
            $headerRow[] = $this->translator->trans($column->getHeader());
        }
        $spreadsheetPackage->setHeader($headerRow);

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
            $columnNames = range('A', 'Z');
            $totalRow = [];
            $totalColumn = 1;
            foreach ($columns as $column) {
                $formula = null;
                if (\in_array($column->getName(), $totalColumns)) {
                    $columnName = $columnNames[$totalColumn - 1];
                    $formula = \sprintf('=SUM(%s2:%s%s)', $columnName, $columnName, $currentRow);
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
            'duration' => new DurationFormatter(),
            default => new DefaultFormatter()
        };
    }

    /**
     * @return array<Column>
     */
    private function getColumns(TimesheetQuery $query): array
    {
        $showRates = $this->isRenderRate($query);

        $columns = [];

        $columns[] = (new Column('date', $this->getFormatter('date')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getBegin());
        $columns[] = (new Column('begin', $this->getFormatter('time')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getBegin());
        $columns[] = (new Column('end', $this->getFormatter('time')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getEnd());
        $columns[] = (new Column('duration', $this->getFormatter('duration')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getDuration());

        if ($showRates) {
            $columns[] = (new Column('currency', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getCurrency());
            $columns[] = (new Column('rate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getRate());
            $columns[] = (new Column('internalRate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getInternalRate());
            $columns[] = (new Column('hourlyRate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getHourlyRate());
            $columns[] = (new Column('fixedRate', new RateFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getFixedRate());
        }

        $columns[] = (new Column('username', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getUser()?->getDisplayName());
        $columns[] = (new Column('account_number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getUser()?->getAccountNumber());
        $columns[] = (new Column('customer', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getName());
        $columns[] = (new Column('project', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getName());
        $columns[] = (new Column('activity', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getActivity()?->getName());
        $columns[] = (new Column('description', new TextFormatter(true)))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getDescription());
        //$columns[] = (new Column('exported', new BooleanFormatter()))->withExtractor(fn(ExportableItem $exportableItem) => $exportableItem->isExported());
        $columns[] = (new Column('billable', new BooleanFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->isBillable());
        $columns[] = (new Column('tags', new ArrayFormatter()))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getTagsAsArray());
        $columns[] = (new Column('type', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getType());
        $columns[] = (new Column('category', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getCategory());
        $columns[] = (new Column('number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getNumber());
        $columns[] = (new Column('project_number', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getNumber());
        $columns[] = (new Column('vat_id', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getCustomer()?->getVatId());
        $columns[] = (new Column('orderNumber', $this->getFormatter('default')))->withExtractor(fn (ExportableItem $exportableItem) => $exportableItem->getProject()?->getOrderNumber());

        foreach ($this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() === null) {
                continue;
            }
            $columns[] = (new Column('timesheet.meta.' . $metaField->getName(), $this->getFormatter('default')))
                ->withHeader($metaField->getLabel())
                ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                    return $exportableItem->getMetaField($metaField->getName())?->getValue();
                });
        }

        foreach ($this->findMetaColumns(new CustomerMetaDisplayEvent($query->copyTo(new CustomerQuery()), CustomerMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() === null) {
                continue;
            }
            $columns[] = (new Column('customer.meta.' . $metaField->getName(), $this->getFormatter('default')))
                ->withHeader($metaField->getLabel())
                ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                    return $exportableItem->getProject()?->getCustomer()?->getMetaField($metaField->getName())?->getValue();
                });
        }

        foreach ($this->findMetaColumns(new ProjectMetaDisplayEvent($query->copyTo(new ProjectQuery()), ProjectMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() === null) {
                continue;
            }
            $columns[] = (new Column('project.meta.' . $metaField->getName(), $this->getFormatter('default')))
                ->withHeader($metaField->getLabel())
                ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                    return $exportableItem->getProject()?->getMetaField($metaField->getName())?->getValue();
                });
        }

        foreach ($this->findMetaColumns(new ActivityMetaDisplayEvent($query->copyTo(new ActivityQuery()), ActivityMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() === null) {
                continue;
            }
            $columns[] = (new Column('activity.meta.' . $metaField->getName(), $this->getFormatter('default')))
                ->withHeader($metaField->getLabel())
                ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                    return $exportableItem->getActivity()?->getMetaField($metaField->getName())?->getValue();
                });
        }

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->dispatcher->dispatch($event);
        foreach ($event->getPreferences() as $metaField) {
            if ($metaField->getName() === null) {
                continue;
            }
            $columns[] = (new Column('user.meta.' . $metaField->getName(), $this->getFormatter('default')))
                ->withHeader($metaField->getLabel())
                ->withExtractor(function (ExportableItem $exportableItem) use ($metaField) {
                    return $exportableItem->getUser()?->getPreference($metaField->getName())?->getValue();
                });
        }

        return $columns;
    }
}
