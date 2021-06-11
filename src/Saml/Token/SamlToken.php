<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class SamlToken extends AbstractToken implements SamlTokenInterface
{
    public function getCredentials()
    {
        return null;
    }
}
