<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;

final class ExportableResult implements ExportableResultInterface
{
    /**
     * @var array<ExportableResultStatistic>|null
     */
    private ?array $statistics = null;

    /**
     * @param array<ExportableResultInterface> $exportableResults
     */
    public function __construct(
        private readonly array $exportableResults
    )
    {
    }

    /**
     * @param array<string, bool|string|int>|null $options
     * @return array<ExportableResultStatisticInterface>
     */
    public function getStatistic(array $options = null): array
    {
        if ($this->statistics === null) {
            $statistics = [];
            foreach ($this->exportableResults as $exportableResult) {
                foreach ($exportableResult->getStatistic($options) as $statistic) {
                    $id = (string) $statistic->getCustomer()->getId();
                    if (!\array_key_exists($id, $statistics)) {
                        $statistics[$id] = [
                            'customer' => $statistic->getCustomer(),
                            'count' => 0,
                            'rate' => 0,
                            'duration' => 0,
                        ];
                    }
                    $statistics[$id]['count'] += $statistic->getCount();
                    $statistics[$id]['rate'] += $statistic->getRate();
                    $statistics[$id]['duration'] += $statistic->getDuration();
                }
            }

            $this->statistics = [];

            foreach ($statistics as $stats) {
                $this->statistics[] = new ExportableResultStatistic(
                    $stats['customer'],
                    $stats['count'],
                    $stats['duration'],
                    $stats['rate']
                );
            }
        }

        return $this->statistics;
    }

    /**
     * @return array<ExportableItem>
     */
    public function getExportItems(): array
    {
        $results = [];

        foreach ($this->exportableResults as $exportableResult) {
            $results = array_merge($results, $exportableResult->getExportItems());
        }

        return $results;
    }
}
