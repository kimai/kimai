<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use App\WorkingTime\Model\YearSummary;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('all')]
final class WorkingTimeSummary
{
    /**
     * Title of the summary row (a translation key), as shown on the working times page
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    public readonly string $title;
    /**
     * Expected time in seconds
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly int $expectedTime;
    /**
     * Actual time in seconds
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly int $actualTime;

    public function __construct(YearSummary $summary)
    {
        $this->title = $summary->getTitle();
        $this->expectedTime = $summary->getExpectedTime();
        $this->actualTime = $summary->getActualTime();
    }
}
