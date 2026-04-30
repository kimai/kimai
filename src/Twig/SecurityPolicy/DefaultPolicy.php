<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * @deprecated since 2.47.0 - use StrictPolicy instead
 */
#[Exclude]
final class DefaultPolicy implements SecurityPolicyInterface
{
    private SecurityPolicyInterface $securityPolicy;

    public function __construct()
    {
        $this->securityPolicy = new StrictPolicy();
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->securityPolicy->checkSecurity($tags, $filters, $functions);
    }

    public function checkMethodAllowed($obj, $method): void
    {
        $this->securityPolicy->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->securityPolicy->checkPropertyAllowed($obj, $property);
    }
}
