<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml;

use App\Saml\Token\SamlToken;

final class SamlTokenFactory
{
    public function createToken($user, array $attributes, array $roles): SamlToken
    {
        $token = new SamlToken($roles);
        $token->setUser($user);
        $token->setAttributes($attributes);

        return $token;
    }
}
