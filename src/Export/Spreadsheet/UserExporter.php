<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet;

use App\Entity\User;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\UserPreferenceExtractor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class UserExporter
{
    public function __construct(private SpreadsheetExporter $exporter, private AnnotationExtractor $annotationExtractor, private UserPreferenceExtractor $userPreferenceExtractor)
    {
    }

    /**
     * @param User[] $entries
     * @param UserPreferenceDisplayEvent $event
     * @return Spreadsheet
     * @throws Extractor\ExtractorException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(array $entries, UserPreferenceDisplayEvent $event): Spreadsheet
    {
        $columns = array_merge(
            $this->annotationExtractor->extract(User::class),
            $this->userPreferenceExtractor->extract($event)
        );

        return $this->exporter->export($columns, $entries);
    }
}
