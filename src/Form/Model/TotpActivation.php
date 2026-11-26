<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use App\Entity\User;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

/**
 * Holds the pending TOTP secret, which is only stored in the user after the code was confirmed.
 */
final class TotpActivation implements TwoFactorInterface
{
    private ?string $code = null;

    public function __construct(
        private readonly User $user,
        #[\SensitiveParameter]
        private readonly string $secret
    )
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return true;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->user->getTotpAuthenticationUsername();
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
    {
        return $this->user->createTotpAuthenticationConfiguration($this->secret);
    }
}
