<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Tests\Mocks\Saml\SamlAuthFactory;
use OneLogin\Saml2\Utils;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Saml\SamlAuth
 */
class SamlAuthTest extends TestCase
{
    public function testCreateToken()
    {
        $previous = Utils::getProxyVars();
        self::assertFalse($previous);

        $sut = (new SamlAuthFactory($this))->create(null, true);

        $current = Utils::getProxyVars();
        self::assertTrue($current);

        Utils::setProxyVars($previous);
    }
}
