<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Entity\Project;
use App\Entity\ProjectMeta;

final class DefaultProjectImporter extends AbstractProjectImporter
{
    private $timezone;

    protected function convertEntry(Project $project, array $row)
    {
        if ($this->timezone === null) {
            $this->timezone = new \DateTimeZone($this->getDefaultTimezone());
        }

        foreach ($row as $name => $value) {
            switch (strtolower($name)) {
                case 'name':
                    $project->setName(substr($value, 0, 149));
                    break;

                case 'comment':
                    if (!empty($value)) {
                        $project->setComment($value);
                    }
                break;

                case 'ordernumber':
                case 'order-number':
                    if (!empty($value)) {
                        $project->setOrderNumber($value);
                    }
                break;

                case 'orderdate':
                case 'order-date':
                    if (!empty($value)) {
                        $project->setOrderDate(new \DateTime($value, $this->timezone));
                    }
                break;

                case 'color':
                    if (!empty($value)) {
                        $project->setColor($value);
                    }
                break;

                case 'visible':
                    if ($value !== '') {
                        $project->setVisible((bool) $value);
                    }
                break;

                default:
                    if (stripos($name, 'meta.') === 0) {
                        $tmpName = str_replace('meta.', '', $name);
                        $meta = new ProjectMeta();
                        $meta->setIsVisible(true);
                        $meta->setType($tmpName);
                        $meta->setName($tmpName);
                        $meta->setValue($value);
                        $project->setMetaField($meta);
                    }
                break;
            }
        }

        return $project;
    }
}
