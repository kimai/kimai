<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine\Behavior;

interface ModifiedAt
{
    public function getModifiedAt(): ?\DateTimeImmutable;

    public function setModifiedAt(\DateTimeImmutable $dateTime): void;
}
