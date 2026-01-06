<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\SecurityPolicy;

use App\Twig\SecurityPolicy\DefaultPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use Twig\Sandbox\SecurityPolicyInterface;

#[CoversClass(DefaultPolicy::class)]
class DefaultPolicyTest extends AbstractPolicyTestCase
{
    protected function createPolicy(): SecurityPolicyInterface
    {
        return new DefaultPolicy();
    }
}
