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
use App\Export\Package\CellFormatter\CellFormatterInterface;
use App\Export\Package\Column;
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
    private ?TemplateInterface $template = null;
    private ?ColumnConverter $converter = null;

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
        $this->getConverter()->registerFormatter($name, $cellFormatter);
    }

    private function getConverter(): ColumnConverter
    {
        if ($this->converter === null) {
            $this->converter = new ColumnConverter(
                $this->eventDispatcher,
                $this->voter,
                $this->logger
            );
        }

        return $this->converter;
    }

    /**
     * @return array<Column>
     */
    private function getColumns(TimesheetQuery $query): array
    {
        if ($this->template === null) {
            throw new \InvalidArgumentException('Template must be set first');
        }

        return $this->getConverter()->getColumns($this->template, $query);
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
