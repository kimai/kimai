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
        // we do not want to handle URLs that are not in the API scope
        if (!str_starts_with($request->getRequestUri(), '/api/')) {
            return false;
        }

        // API documentation is only available to registered and logged-in users
        if (str_starts_with($request->getRequestUri(), '/api/doc')) {
            return false;
        }

        // ------------------------------------------------------------------------------------
        // the next two checks are primarily here to make sure to return proper error messages

        // let's use this firewall if a Bearer token is set in the header
        // other cases like "bearer" are rejected earlier
        if (($auth = $request->headers->get('Authorization')) !== null && str_starts_with($auth, 'Bearer ')) {
            return true;
        }

        // let's use this firewall if the deprecated username & token combination is available
        if ($request->headers->has(TokenAuthenticator::HEADER_USERNAME) &&
            $request->headers->has(TokenAuthenticator::HEADER_TOKEN)) {
            return true;
        }
        // ------------------------------------------------------------------------------------

        // checking for a previous session allows us to skip the API firewall and token access handler
        // we simply re-use the existing session when doing API calls from the frontend.
        // it is not necessary to check headers. if there is no valid session, we should always use this firewall
        return !$request->hasPreviousSession();
    }
}
