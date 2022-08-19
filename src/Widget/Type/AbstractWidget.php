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

    public function getOptions(array $options = []): array
    {
        return array_merge($this->options, $options);
    }

    public function isInternal(): bool
    {
        return false;
    }
}
