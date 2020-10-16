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
    protected function convertEntry(Project $project, array $row, array $options = []): void
    {
        foreach ($row as $name => $value) {
            switch (strtolower($name)) {
                case 'name':
                    $project->setName(substr($value, 0, 149));
                    break;

                case 'comment':
                case 'description':
                    if (!empty($value)) {
                        $project->setComment($value);
                    }
                break;

                case 'ordernumber':
                case 'order-number':
                case 'order number':
                    if (!empty($value)) {
                        $project->setOrderNumber(substr($value, 0, 20));
                    }
                break;

                case 'orderdate':
                case 'order-date':
                case 'order date':
                    if (!empty($value)) {
                        $timezone = $project->getCustomer()->getTimezone();
                        if (isset($options['timezone'])) {
                            $timezone = $options['timezone'];
                        }
                        $timezone = new \DateTimeZone($timezone ?? date_default_timezone_get());
                        if (isset($options['dateformat'])) {
                            $date = \DateTime::createFromFormat($options['dateformat'], $value, $timezone);
                        } else {
                            $date = new \DateTime($value, $timezone);
                        }
                        if ($date instanceof \DateTime) {
                            $project->setOrderDate($date);
                        } else {
                            throw new \InvalidArgumentException('Invalid order date: ' . $value);
                        }
                    }
                break;

                case 'color':
                    if (!empty($value)) {
                        $project->setColor($value);
                    }
                break;

                case 'budget':
                    if (!empty($value)) {
                        $project->setBudget($value);
                    }
                break;

                case 'time budget':
                case 'time-budget':
                    if (!empty($value)) {
                        $project->setTimeBudget($value);
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
                        $meta->setName($tmpName);
                        $meta->setValue($value);
                        $project->setMetaField($meta);
                    }
                break;
            }
        }
    }
}
