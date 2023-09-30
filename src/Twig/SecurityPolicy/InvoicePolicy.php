<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Represents the security policy for custom Twig invoice templates.
 */
final class InvoicePolicy implements SecurityPolicyInterface
{
    private ChainPolicy $policy;

    public function __construct()
    {
        $this->policy = new ChainPolicy();
        $this->policy->addPolicy(new DefaultPolicy());
        $this->policy->addPolicy(new SecurityPolicy(
            [], // tags
            ['map', 'escape', 'trans', 'nl2str', 'default', 'md2html', 'nl2br', 'trim', 'raw', 'date_short', 'duration', 'amount', 'money', 'join'], // filters
            [], // methods
            [], // properties
            [] // functions
        ));
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->policy->checkSecurity($tags, $filters, $functions);
    }

    public function checkMethodAllowed($obj, $method): void
    {
        $this->policy->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->policy->checkPropertyAllowed($obj, $property);
    }
}
