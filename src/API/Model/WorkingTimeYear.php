<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use App\WorkingTime\Model\Year;
use App\WorkingTime\Model\YearPerUserSummary;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[Serializer\ExclusionPolicy('all')]
final class WorkingTimeYear
{
    /**
     * User ID
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly int $user;
    /**
     * The year, eg. 2026
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly int $year;
    /**
     * Expected times are only counted up to (and including) this day
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: "DateTime<'Y-m-d'>")]
    public readonly \DateTimeInterface $until;
    /**
     * Whether the user has a work contract configured
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    public readonly bool $workContract;
    /**
     * Expected time of the year in seconds, up to the "until" date
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly int $expectedTime;
    /**
     * Actual time of the year in seconds
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly int $actualTime;
    /**
     * @var array<WorkingTimeMonth>
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'array<App\API\Model\WorkingTimeMonth>')]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: WorkingTimeMonth::class)))]
    public readonly array $months;
    /**
     * Additional summary rows (eg. provided by plugins), as shown on the working times page
     *
     * @var array<WorkingTimeSummary>
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'array<App\API\Model\WorkingTimeSummary>')]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: WorkingTimeSummary::class)))]
    public readonly array $summaries;

    public function __construct(Year $year, YearPerUserSummary $summary, \DateTimeInterface $until)
    {
        $this->user = (int) $year->getUser()->getId();
        $this->year = (int) $year->getYear()->format('Y');
        $this->until = $until;
        $this->workContract = $year->getUser()->hasWorkHourConfiguration();
        $this->expectedTime = $year->getExpectedTime($until);
        $this->actualTime = $year->getActualTime();

        $months = [];
        foreach ($year->getMonths() as $month) {
            $months[] = new WorkingTimeMonth($month, $until);
        }
        $this->months = $months;

        $summaries = [];
        foreach ($summary->getSummaries() as $row) {
            $summaries[] = new WorkingTimeSummary($row);
        }
        $this->summaries = $summaries;
    }
}
