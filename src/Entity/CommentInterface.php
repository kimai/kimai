<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

interface CommentInterface
{
    public function getId(): ?int;

    public function getMessage(): ?string;

    public function setMessage(string $message): void;

    public function getCreatedBy(): ?User;

    public function setCreatedBy(User $createdBy): void;

    public function getCreatedAt(): ?\DateTime;

    public function setCreatedAt(\DateTime $createdAt): void;

    public function isPinned(): bool;

    public function setPinned(bool $pinned): void;
}
