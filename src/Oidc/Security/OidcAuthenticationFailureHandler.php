<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc\Security;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

final class OidcAuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    protected $defaultOptions = [
        'failure_path' => 'login',
        'failure_forward' => false,
        'login_path' => 'oidc_login',
        'failure_path_parameter' => '_failure_path',
    ];
}
