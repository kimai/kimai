<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Customer;
use App\Entity\ExportableItem;

final class ExportableArrayResult implements ExportableResultInterface
{
    /**
     * @var array<ExportableResultStatistic>|null
     */
    private ?array $statistic = null;

    /**
     * @param array<ExportableItem> $items
     */
    public function __construct(private readonly array $items)
    {
    }

    /**
     * @param array<string, bool|string|int>|null $options
     * @return array<ExportableResultStatisticInterface>
     */
    public function getStatistic(array $options = null): array
    {
        if ($this->statistic === null) {
            /** @var array<int, array{customer: Customer, count: non-negative-int, rate: float, duration: int}> $byCustomer */
            $byCustomer = [];
            foreach ($this->items as $item) {
                $customer = $item->getProject()?->getCustomer();
                if ($customer === null) {
                    throw new \InvalidArgumentException('Found export item with empty project or customer: ' . $item->getId());
                }
                $cid = $customer->getId();
                if (!isset($byCustomer[$cid])) {
                    $byCustomer[$cid] = [
                        'customer' => $customer,
                        'count' => 0,
                        'rate' => 0,
                        'duration' => 0,
                    ];
                }
                $byCustomer[$cid]['count']++;
                $byCustomer[$cid]['rate'] += $item->getRate();
                $byCustomer[$cid]['duration'] += $item->getDuration() ?? 0;
            }

            $this->statistic = [];
            foreach ($byCustomer as $stats) {
                $this->statistic[] = new ExportableResultStatistic(
                    $stats['customer'],
                    $stats['count'],
                    $stats['duration'],
                    $stats['rate']
                );
            }
        }

        return $this->statistic;
    }

    /**
     * @return array<ExportableItem>
     */
    public function getExportItems(): array
    {
        return $this->items;
    }
}
