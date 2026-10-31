<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;

final class ExportablePreviewResult implements ExportableResultInterface
{
    /**
     * @param array<ExportableItem> $items
     * @param array<ExportableResultStatisticInterface> $statistic
     */
    public function __construct(
        private readonly array $items,
        private readonly array $statistic
    )
    {
    }

    /**
     * @param array<string, bool|string|int>|null $options
     * @return array<ExportableResultStatisticInterface>
     */
    public function getStatistic(array $options = null): array
    {
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
