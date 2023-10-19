<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use Twig\Sandbox\SecurityPolicyInterface;

final class ChainPolicy implements SecurityPolicyInterface
{
    /** @var array<SecurityPolicyInterface> */
    private array $policies = [];

    public function __construct()
    {
    }

    public function addPolicy(SecurityPolicyInterface $policy): void
    {
        $this->policies[] = $policy;
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($this->policies as $policy) {
            $policy->checkSecurity($tags, $filters, $functions);
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        foreach ($this->policies as $policy) {
            $policy->checkMethodAllowed($obj, $method);
        }
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        foreach ($this->policies as $policy) {
            $policy->checkPropertyAllowed($obj, $property);
        }
    }
}
