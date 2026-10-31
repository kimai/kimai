<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;

interface ExportableResultInterface
{
    /**
     * @param array<string, bool|string|int>|null $options
     * @return array<ExportableResultStatisticInterface>
     */
    public function getStatistic(array $options = null): array;

    /**
     * @return array<ExportableItem>
     */
    public function getExportItems(): array;
}
