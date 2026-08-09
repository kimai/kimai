<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Attribute;

/**
 * Declares which API-token scope is required to call an API endpoint.
 *
 * Usage:
 *   #[ApiToken('customer')]              on the controller class: resource "customer",
 *                                        the action is derived from the HTTP verb.
 *   #[ApiToken(action: 'update')]        on a method: overrides the derived action.
 *   #[ApiToken('customer', 'update')]    on a method: overrides resource and action.
 *   #[ApiToken(ignore: true)]            marks an endpoint (or a whole controller) as
 *                                        "no scope required" (e.g. status/ping, /me).
 *
 * A method-level attribute always takes precedence over the class-level one.
 * Endpoints without any ApiToken declaration are allowed by default (see the
 * enforcement subscriber); a guard test ensures every core endpoint is declared.
 *
 * The declaration is resolved once at cache warmup, not at runtime.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class ApiToken
{
    public function __construct(
        public readonly ?string $resource = null,
        public readonly ?string $action = null,
        public readonly bool $ignore = false,
    )
    {
    }
}
