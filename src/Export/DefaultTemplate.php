<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\MetaTableTypeInterface;
use App\Entity\User;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class DefaultTemplate implements TemplateInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $id,
        private readonly ?string $locale = 'en',
        private readonly string $title = 'default',
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [];
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
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
     * @return array<int, string>
     */
    public function getColumns(TimesheetQuery $query): array
    {
        // @deprecated from 2.36 - will be removed with 3.0
        $durationFormatter = 'duration';
        if (($user = $query->getCurrentUser()) instanceof User) {
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

        foreach ($this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT)) as $metaField) {
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
