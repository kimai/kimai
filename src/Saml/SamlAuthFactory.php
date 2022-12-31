<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml;

use App\Configuration\SamlConfigurationInterface;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @final
 */
class SamlAuthFactory
{
    public function __construct(
        private RequestStack $request,
        private SamlConfigurationInterface $configuration
    ) {
    }

    public function create(): Auth
    {
        if (null !== $this->request->getMainRequest() && $this->request->getMainRequest()->isFromTrustedProxy()) {
            Utils::setProxyVars(true);
        }

        return new Auth($this->configuration->getConnection());
    }
}
