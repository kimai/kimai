<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;
use App\Repository\Query\ExportQuery;

interface ExportableRepositoryInterface
{
    public function getPreview(ExportQuery $query, int $maxItems): ExportableResultInterface;

    /**
     * @return array<ExportableItem>
     */
    public function getResult(ExportQuery $query): array;
}
