<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Model;

use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;

/**
 * Class to represent the view time records.
 */
class TimeRecord
{
    /** @var array<string> */
    public const VALID_MERGE_MODES = [
        RecordMergeMode::MODE_MERGE,
        RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY,
        RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY,
    ];

    private ?string $description = null;
    /**
     * @var array<array{'hourlyRate': float, 'duration': int}>
     */
    private array $hourlyRates = [];
    private float $rate = 0.0;
    private int $duration = 0;
    private ?Project $project = null;

    private function __construct(
        private readonly \DateTimeInterface $date,
        private readonly User $user,
        private readonly string $mergeMode
    )
    {
    }

    public static function fromTimesheet(Timesheet $timesheet, string $mergeMode = RecordMergeMode::MODE_MERGE): TimeRecord
    {
        if (!\in_array($mergeMode, self::VALID_MERGE_MODES, true)) {
            throw new \InvalidArgumentException("Invalid merge mode given: $mergeMode");
        }

        if ($timesheet->getBegin() === null) {
            throw new \InvalidArgumentException('Timesheet without begin date is not supported');
        }

        if ($timesheet->getUser() === null) {
            throw new \InvalidArgumentException('Timesheet without user is not supported');
        }

        $record = new TimeRecord($timesheet->getBegin(), $timesheet->getUser(), $mergeMode);
        $record->addTimesheet($timesheet);

        if ($timesheet->getProject() !== null) {
            $record->setProject($timesheet->getProject());
        }

        return $record;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<array{'hourlyRate': float, 'duration': int}>
     */
    public function getHourlyRates(): array
    {
        return $this->hourlyRates;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    // Helper methods

    public function hasDifferentHourlyRates(): bool
    {
        return \count($this->hourlyRates) > 1;
    }

    public function addTimesheet(Timesheet $timesheet): void
    {
        $this->addHourlyRate($timesheet->getHourlyRate(), $timesheet->getDuration());
        $this->addRate($timesheet->getRate());
        $this->addDuration($timesheet->getDuration());
        $this->setDescription($timesheet);
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    protected function addHourlyRate(?float $hourlyRate, ?int $duration): void
    {
        if ($hourlyRate > 0 && $duration > 0) {
            $entryIndex = null;
            foreach ($this->hourlyRates as $index => $info) {
                if ($info['hourlyRate'] === $hourlyRate) {
                    $entryIndex = $index;
                    break;
                }
            }

            if ($entryIndex === null) {
                $this->hourlyRates[] = [
                    'hourlyRate' => $hourlyRate,
                    'duration' => $duration,
                ];
            } else {
                $this->hourlyRates[$entryIndex]['duration'] += $duration;
            }
        }
    }

    private function addRate(?float $rate): void
    {
        if ($rate !== null) {
            $this->rate += $rate;
        }
    }

    private function addDuration(?int $duration): void
    {
        if ($duration !== null) {
            $this->duration += $duration;
        }
    }

    protected function setDescription(Timesheet $timesheet): void
    {
        $description = $timesheet->getDescription();

        // Merge description dependent on record merge mode
        if ($this->description === null) {
            $this->description = $description;
        } elseif ($this->mergeMode === RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY && $this->getDate() < $timesheet->getBegin()) {
            // Override description on last
            $this->description = $timesheet->getDescription();
        } elseif ($this->mergeMode === RecordMergeMode::MODE_MERGE) {
            // MODE_MERGE
            if ($description !== null && \strlen($description) > 0) {
                $this->description = (
                    implode(PHP_EOL, [
                        $this->getDescription(),
                        $description
                    ])
                );
            }
        }
    }

    protected function setProject(Project $project): void
    {
        $this->project = $project;
    }
}
