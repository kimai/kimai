<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Entity\User;
use App\Saml\SamlToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SamlToken::class)]
class SamlTokenTest extends TestCase
{
    public function testConstruct(): void
    {
        $user = new User();
        $user->setUserIdentifier('foo');
        $sut = new SamlToken($user, 'firewallName', []);
        self::assertEquals('foo', $sut->getUserIdentifier());
    }
}
