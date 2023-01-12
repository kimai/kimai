<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Authentication;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final class ApiTokenUpgradeBadge implements BadgeInterface
{
    /**
     * @param string|null $plaintextApiToken
     * @param PasswordUpgraderInterface<User> $passwordUpgrader
     */
    public function __construct(private ?string $plaintextApiToken, private PasswordUpgraderInterface $passwordUpgrader)
    {
    }

    public function getAndErasePlaintextApiToken(): string
    {
        $password = $this->plaintextApiToken;
        if (null === $password) {
            throw new LogicException('The api token is erased as another listener already used this badge.');
        }

        $this->plaintextApiToken = null;

        return $password;
    }

    /**
     * @return PasswordUpgraderInterface<User>
     */
    public function getPasswordUpgrader(): PasswordUpgraderInterface
    {
        return $this->passwordUpgrader;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
