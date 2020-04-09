<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

/**
 * @internal
 */
interface RateInterface
{
    public function getUser(): ?User;

    public function getRate(): float;

    public function getInternalRate(): ?float;

    public function isFixed(): bool;

    public function getScore(): int;
}
