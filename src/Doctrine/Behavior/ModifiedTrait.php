<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine\Behavior;

use Doctrine\ORM\Mapping as ORM;

trait ModifiedTrait
{
    #[ORM\Column(name: 'modified_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeImmutable $dateTime): void
    {
        $this->modifiedAt = $dateTime;
    }
}
