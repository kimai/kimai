<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Widget\WidgetInterface;
use Symfony\Component\Form\Form;

abstract class AbstractWidget implements WidgetInterface
{
    private array $options = [];
    private ?User $user = null;

    public function getTranslationDomain(): string
    {
        return 'messages';
    }

    public function hasForm(): bool
    {
        return false;
    }

    public function getForm(): ?Form
    {
        return null;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_SMALL;
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_SMALL;
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

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge($this->options, $options);
    }

    public function isInternal(): bool
    {
        return false;
    }

    protected function createDate(string $date): \DateTime
    {
        return new \DateTime($date, $this->getTimezone());
    }

    protected function createMonthStartDate(): \DateTime
    {
        return $this->createDate('first day of this month 00:00:00');
    }

    protected function createMonthEndDate(): \DateTime
    {
        return $this->createDate('last day of this month 23:59:59');
    }

    protected function createWeekStartDate(): \DateTime
    {
        return $this->createDate('monday this week 00:00:00');
    }

    protected function createWeekEndDate(): \DateTime
    {
        return $this->createDate('sunday this week 23:59:59');
    }

    protected function createTodayStartDate(): \DateTime
    {
        return $this->createDate('00:00:00');
    }

    protected function createTodayEndDate(): \DateTime
    {
        return $this->createDate('23:59:59');
    }

    public function getTimezone(): \DateTimeZone
    {
        $timezone = date_default_timezone_get();
        if (null !== $this->user) {
            $timezone = $this->user->getTimezone();
        }

        return new \DateTimeZone($timezone);
    }

    public function getTemplateName(): string
    {
        $name = (new \ReflectionClass($this))->getShortName();

        return sprintf('widget/widget-%s.html.twig', strtolower($name));
    }
}
