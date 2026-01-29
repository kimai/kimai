<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

final class DayAddon
{
    private bool $billable = true;
    /** @var array<string, mixed> */
    private array $attributes = [];

    public function __construct(
        private readonly string $title,
        private readonly int $duration,
        private readonly int $visibleDuration,
        private readonly ?string $type = null
    )
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function setBillable(bool $billable): void
    {
        $this->billable = $billable;
    }

    public function getVisibleDuration(): int
    {
        return $this->visibleDuration;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set a custom attribute for plugin-specific data.
     *
     * Example attributes:
     * - 'comment': Additional text to display in tooltip
     * - 'half_day': Boolean flag for half-day entries
     * - 'approval_status': Status like 'pending', 'approved', 'rejected'
     */
    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $key): bool
    {
        return \array_key_exists($key, $this->attributes);
    }
}
