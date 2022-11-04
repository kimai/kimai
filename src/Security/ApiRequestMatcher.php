<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class ApiRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    {
        if (strpos($request->getRequestUri(), '/api/doc') !== false) {
            return false;
        }

        if (!preg_match('{^/api/}', rawurldecode($request->getPathInfo()))) {
            return false;
        }

        return $request->headers->has(TokenAuthenticator::HEADER_USERNAME) && $request->headers->has(TokenAuthenticator::HEADER_TOKEN);
    }
}
