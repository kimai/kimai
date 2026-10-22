<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\MetaTableTypeInterface;
use App\Repository\Query\ExportQuery;

interface ExportPreviewColumnProviderInterface
{
    /**
     * @return MetaTableTypeInterface[]
     */
    public function getExportPreviewColumns(ExportQuery $query): array;
}
