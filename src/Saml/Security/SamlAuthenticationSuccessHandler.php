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
        // see https://docs.oasis-open.org/security/saml/v2.0/saml-bindings-2.0-os.pdf
        // if using the Deflate encoding, RelayState will be submitted using the query
        $relayState = $request->request->get('RelayState', $request->query->get('RelayState'));

        if (\is_string($relayState)) {
            $values = parse_url($relayState);

            // we use only the path part of the URL to prevent external redirects
            $path = null;
            if (\is_array($values) && \array_key_exists('path', $values)) {
                $path = $values['path'];
            }

            if (\is_string($path)
                && $path !== ''
                && str_starts_with($path, '/')
                && !str_starts_with($path, '//')
                && !str_contains($path, '\\')
            ) {
                $target = $this->httpUtils->generateUri($request, $path);
                $loginUrl = $this->httpUtils->generateUri($request, (string) $this->options['login_path']);

                if (\array_key_exists('scheme', $values) && str_starts_with($values['scheme'], 'http')) {
                    if (\array_key_exists('host', $values) && !str_starts_with($target, $values['scheme'] . '://' . $values['host'])) {
                        $target = null;
                    }
                }

                // make sure that the login URL is not the target, which would be an endless loop for the user
                if ($target !== null && $target !== $loginUrl) {
                    return $target;
                }
            }
        }

        return parent::determineTargetUrl($request);
    }
}
