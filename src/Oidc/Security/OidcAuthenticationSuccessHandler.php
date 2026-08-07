<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc\Security;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

final class OidcAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    public function __construct(HttpUtils $httpUtils)
    {
        parent::__construct($httpUtils, [
            'always_use_default_target_path' => false,
            'default_target_path' => 'homepage',
            'login_path' => 'oidc_login',
            'target_path_parameter' => '_target_path',
            'use_referer' => false,
        ]);
    }
}
