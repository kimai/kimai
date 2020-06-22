<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml;

use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;
use Symfony\Component\HttpFoundation\RequestStack;

class SamlAuth extends Auth
{
    public function __construct(RequestStack $request, array $settings = null)
    {
        parent::__construct($settings);

        if (null !== $request->getMasterRequest() && $request->getMasterRequest()->isFromTrustedProxy()) {
            Utils::setProxyVars(true);
        }
    }
}
