<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final class OidcBadge implements BadgeInterface
{
    public function __construct(private readonly OidcLoginAttributes $oidcLoginAttributes)
    {
    }

    public function getOidcLoginAttributes(): OidcLoginAttributes
    {
        return $this->oidcLoginAttributes;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
