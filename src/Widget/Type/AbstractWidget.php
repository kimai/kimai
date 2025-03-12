<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Timesheet\DateRangeEnum;
use App\Widget\WidgetInterface;

abstract class AbstractWidget implements WidgetInterface
{
    private array $options = [];
    private ?User $user = null;

    public function getDateRangeColor(DateRangeEnum $dateRangeEnum): string
    {
        return match ($dateRangeEnum) {
            DateRangeEnum::TODAY => 'green',
            DateRangeEnum::WEEK => 'blue',
            DateRangeEnum::MONTH => 'purple',
            DateRangeEnum::FINANCIAL, DateRangeEnum::YEAR => 'yellow',
            DateRangeEnum::TOTAL => 'red',
        };
    }

    public function getDateRangeTitle(DateRangeEnum $dateRangeEnum): string
    {
        return match ($dateRangeEnum) {
            DateRangeEnum::TODAY => 'daterangepicker.today',
            DateRangeEnum::WEEK => 'daterangepicker.thisWeek',
            DateRangeEnum::MONTH => 'daterangepicker.thisMonth',
            DateRangeEnum::YEAR => 'daterangepicker.thisYear',
            DateRangeEnum::FINANCIAL => 'daterangepicker.thisFinancialYear',
            DateRangeEnum::TOTAL => 'daterangepicker.allTime',
        };
    }

    public function getTranslationDomain(): string
    {
        return 'messages';
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_NORMAL;
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setOption(string $name, string|bool|int|float $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @return array<string, string|bool|int|float>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isInternal(): bool
    {
        return false;
    }

    public function getTemplateName(): string
    {
        return \sprintf('widget/widget-%s.html.twig', strtolower($this->getId()));
    }
}
