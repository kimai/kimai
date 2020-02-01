<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Entity\User;
use App\Saml\SamlTokenFactory;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Saml\SamlTokenFactory
 */
class SamlTokenFactoryTest extends TestCase
{
    public function testCreateToken()
    {
        $user = new User();
        $user->setUsername('foobar');

        $factory = new SamlTokenFactory();
        $sut = $factory->createToken($user, ['foo' => 'bar', 'bar' => 'world'], ['ROLE_ADMIN', 'ROLE_TEST']);

        self::assertInstanceOf(SamlToken::class, $sut);
        self::assertEquals('bar', $sut->getAttribute('foo'));
        self::assertEquals('world', $sut->getAttribute('bar'));
        self::assertEquals(['ROLE_ADMIN', 'ROLE_TEST'], $sut->getRoleNames());
        self::assertSame($user, $sut->getUser());
        self::assertEquals('foobar', $sut->getUsername());
    }
}
