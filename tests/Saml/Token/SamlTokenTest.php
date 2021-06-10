<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Token;

use App\Saml\Token\SamlToken;
use App\Saml\Token\SamlTokenInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Saml\Token\SamlToken
 */
class SamlTokenTest extends TestCase
{
    public function testCreateToken()
    {
        $sut = new SamlToken();

        self::assertInstanceOf(SamlTokenInterface::class, $sut);
        self::assertNull($sut->getCredentials());
    }
}
