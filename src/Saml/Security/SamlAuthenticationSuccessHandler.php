<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;

final class SamlAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    protected $defaultOptions = [
        'always_use_default_target_path' => false,
        'default_target_path' => '/',
        'login_path' => 'saml_login',
        'target_path_parameter' => '_target_path',
        'use_referer' => false,
    ];

    protected function determineTargetUrl(Request $request): string
    {
        $relayState = $request->get('RelayState');
        if (\is_scalar($relayState)) {
            $relayState = (string) $relayState;
            if ($relayState !== $this->httpUtils->generateUri($request, (string) $this->options['login_path'])) {
                return $relayState;
            }
        }

        return parent::determineTargetUrl($request);
    }
}
