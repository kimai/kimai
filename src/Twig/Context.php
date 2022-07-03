<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\SystemConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;

final class Context
{
    public function __construct(private SystemConfiguration $systemConfiguration, private RequestStack $requestStack)
    {
    }

    public function isModalRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return true;
        }

        if (!$request->headers->has('X-Requested-With')) {
            return false;
        }

        return str_contains(strtolower($request->headers->get('X-Requested-With')), 'kimai-modal');
    }

    public function isJavascriptRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return true;
        }

        if (!$request->headers->has('X-Requested-With')) {
            return false;
        }

        return str_contains(strtolower($request->headers->get('X-Requested-With')), 'kimai');
    }

    public function getBranding(string $config): mixed
    {
        @trigger_error('Use "kimai_config" instead of "kimai_context" to access system configurations', E_USER_DEPRECATED);

        return $this->systemConfiguration->find('theme.branding.' . $config);
    }
}
