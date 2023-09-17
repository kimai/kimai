<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUserEntity implements UserInterface
{
    public function getRoles(): array
    {
        return [];
    }

    public function getUserIdentifier(): string
    {
        return 'foo';
    }

    public function eraseCredentials(): void
    {
    }

    public function getTimeFormat(): string
    {
        return 'H:i';
    }
}
