<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Permission;

use App\Entity\AccessToken;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Short-lived bridge that carries the authenticated AccessToken within a request:
 * the AccessTokenHandler stores it here, the AccessTokenSuccessHandler reads it to
 * copy the scopes onto the security token, and the GET /api/token endpoint reads
 * it to build the effective scope matrix.
 *
 * THIS CLASS IS A LIVING WORKAROUND, BECAUSE:
 * Symfony's AccessTokenAuthenticator does not let the token handler attach data to
 * the resulting security token directly, nor forward additional badges. The state
 * is reset between requests via ResetInterface.
 */
final class ApiTokenContext implements ResetInterface
{
    private ?AccessToken $token = null;

    public function setToken(AccessToken $token): void
    {
        $this->token = $token;
    }

    public function getToken(): ?AccessToken
    {
        return $this->token;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->token = null;
    }
}
