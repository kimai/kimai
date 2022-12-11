<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final class ApiRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    {
        if (str_contains($request->getRequestUri(), '/api/doc')) {
            return false;
        }

        if (str_contains($request->getRequestUri(), '/api/')) {
            return false;
        }

        return !$request->headers->has(SessionAuthenticator::HEADER_JAVASCRIPT) &&
                $request->headers->has(TokenAuthenticator::HEADER_USERNAME) &&
                $request->headers->has(TokenAuthenticator::HEADER_TOKEN);
    }
}
