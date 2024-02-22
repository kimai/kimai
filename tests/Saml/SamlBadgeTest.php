<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Saml\SamlBadge;
use App\Saml\SamlLoginAttributes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Saml\SamlBadge
 */
class SamlBadgeTest extends TestCase
{
    public function testConstruct(): void
    {
        $attributes = new SamlLoginAttributes();
        $sut = new SamlBadge($attributes);
        self::assertTrue($sut->isResolved());
        self::assertEquals($attributes, $sut->getSamlLoginAttributes());
    }
}
