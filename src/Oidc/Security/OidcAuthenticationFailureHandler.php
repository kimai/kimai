<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc\Security;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;

final class OidcAuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils)
    {
        parent::__construct($httpKernel, $httpUtils, [
            'failure_path' => 'login',
            'failure_forward' => false,
            'login_path' => 'login',
            'failure_path_parameter' => '_failure_path',
        ]);
    }
}
