<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\SecurityPolicy;

use App\Twig\SecurityPolicy\ChainPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityPolicyInterface;

#[CoversClass(ChainPolicy::class)]
class ChainPolicyTest extends TestCase
{
    public function testCheckSecurity(): void
    {
        $policy1 = $this->createMock(SecurityPolicyInterface::class);
        $policy1->expects(self::once())->method('checkSecurity')->with(['tag'], ['filter'], ['function']);

        $policy2 = $this->createMock(SecurityPolicyInterface::class);
        $policy2->expects(self::once())->method('checkSecurity')->with(['tag'], ['filter'], ['function']);

        $sut = new ChainPolicy();
        $sut->addPolicy($policy1);
        $sut->addPolicy($policy2);

        $sut->checkSecurity(['tag'], ['filter'], ['function']);
    }

    public function testCheckMethodAllowed(): void
    {
        $obj = new \stdClass();
        $policy1 = $this->createMock(SecurityPolicyInterface::class);
        $policy1->expects(self::once())->method('checkMethodAllowed')->with($obj, 'method');

        $policy2 = $this->createMock(SecurityPolicyInterface::class);
        $policy2->expects(self::once())->method('checkMethodAllowed')->with($obj, 'method');

        $sut = new ChainPolicy();
        $sut->addPolicy($policy1);
        $sut->addPolicy($policy2);

        $sut->checkMethodAllowed($obj, 'method');
    }

    public function testCheckPropertyAllowed(): void
    {
        $obj = new \stdClass();
        $policy1 = $this->createMock(SecurityPolicyInterface::class);
        $policy1->expects(self::once())->method('checkPropertyAllowed')->with($obj, 'property');

        $policy2 = $this->createMock(SecurityPolicyInterface::class);
        $policy2->expects(self::once())->method('checkPropertyAllowed')->with($obj, 'property');

        $sut = new ChainPolicy();
        $sut->addPolicy($policy1);
        $sut->addPolicy($policy2);

        $sut->checkPropertyAllowed($obj, 'property');
    }
}
