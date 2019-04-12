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
    /**
     * @var array
     */
    protected $typeArray = [];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string $type
     * @return ExportQuery
     */
    public function addType(string $type)
    {
        $this->typeArray[] = $type;

        return $this;
    }

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
        if (in_array($type, $this->typeArray)) {
            $this->type = $type;
        }

        return $this;
    }
}
