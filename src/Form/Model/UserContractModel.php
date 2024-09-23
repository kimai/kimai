<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use App\Entity\User;

final class UserContractModel
{
    public function __construct(private readonly User $user)
    {
    }

    public function __isset(string $name): bool
    {
        return true;
    }

    public function __set(string $name, mixed $value): void
    {
        $method = 'set' . ucfirst($name);

        if (method_exists($this->user, $method)) {
            $this->user->$method($value);

            return;
        }

        if (!\is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException('Invalid value passed');
        }

        $this->user->setPreferenceValue($name, $value);
    }

    public function __get(string $name): mixed
    {
        $method = 'get' . ucfirst($name);

        if (method_exists($this->user, $method)) {
            return $this->user->$method();
        }

        return $this->user->getPreferenceValue($name);
    }
}
