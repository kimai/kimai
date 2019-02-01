<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

/**
 * Can be used for export queries.
 */
class ExportQuery extends TimesheetQuery
{
    public const TYPE_HTML = 'html';
    public const TYPE_CSV = 'csv';
    public const TYPE_PDF = 'pdf';
    public const TYPE_XLSX = 'xlsx';
    public const TYPE_ODS = 'ods';

    /**
     * @var string
     */
    protected $type;

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ExportQuery
     */
    public function setType(string $type)
    {
        if (!in_array($type, [self::TYPE_PDF, self::TYPE_CSV, self::TYPE_HTML, self::TYPE_XLSX, self::TYPE_ODS])) {
            throw new \InvalidArgumentException('Invalid export type');
        }
        $this->type = $type;
        return $this;
    }
}
