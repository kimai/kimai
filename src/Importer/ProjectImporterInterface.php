<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Entity\Project;

interface ProjectImporterInterface
{
    /**
     * Convert an entry (key-value pairs) to a Project entity.
     * Accepts an optional array of options (like "dateformat" or "timezone").
     *
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $options
     * @return Project
     */
    public function convertEntryToProject(array $entry, array $options = []): Project;
}
