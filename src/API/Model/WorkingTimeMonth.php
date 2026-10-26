<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use App\WorkingTime\Model\Month;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('all')]
final class WorkingTimeMonth
{
    /**
     * Month in the format YYYY-MM
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    public readonly string $month;
    /**
     * Expected time in seconds, counted up to (and including) the "until" date
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
    /**
     * Whether every day of this month is approved
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    public readonly bool $locked;
    /**
     * When the month was approved
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: "DateTime<'Y-m-d\TH:i:sO'>")]
    public readonly ?\DateTimeInterface $lockDate;
    /**
     * ID of the user who approved the month
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    public readonly ?int $lockedBy;

    public function __construct(Month $month, \DateTimeInterface $until)
    {
        $this->month = $month->getMonth()->format('Y-m');
        $this->expectedTime = $month->getExpectedTime($until);
        $this->actualTime = $month->getActualTime();
        $this->locked = $month->isLocked();
        $this->lockDate = $month->getLockDate();
        $this->lockedBy = $month->getLockedBy()?->getId();
    }
}
