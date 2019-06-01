<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Configuration\LdapConfiguration;
use App\Entity\User;
use App\Ldap\LdapUserHydrator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\LdapUserHydrator
 */
class LdapUserHydratorTest extends TestCase
{
    public function testEmptyHydrate()
    {
        $config = new LdapConfiguration([
            'active' => false,
            'connection' => [
                'host' => '1.1.1.1'
            ],
            'user' => [
                'attributes' => []
            ]
        ]);

        $sut = new LdapUserHydrator($config);
        $user = $sut->hydrate([]);
        self::assertInstanceOf(User::class, $user);
        self::assertEmpty($user->getUsername());
        self::assertEmpty($user->getEmail());
    }

    public function testHydrate()
    {
        $config = new LdapConfiguration([
            'active' => false,
            'connection' => [
                'host' => '1.1.1.1'
            ],
            'user' => [
                'attributes' => [
                    ['ldap_attr' => 'uid', 'user_method' => 'setUsername'],
                    ['ldap_attr' => 'foo', 'user_method' => 'setAlias'],
                    ['ldap_attr' => 'bar', 'user_method' => 'setTitle'],
                    ['ldap_attr' => 'xxxxxxxx', 'user_method' => 'setAvatar'],
                ]
            ]
        ]);

        $ldapEntry = [
            'uid' => ['Karl-Heinz'],
            'blub' => ['dfsdfsdf'],
            'foo' => ['bar'],
            'bar' => ['foo'],
            'xxxxxxxx' => ['https://www.example.com'],
            'blub1' => ['dfsdfsdf'],
        ];

        $sut = new LdapUserHydrator($config);
        $user = $sut->hydrate($ldapEntry);
        self::assertInstanceOf(User::class, $user);
        self::assertEquals('Karl-Heinz', $user->getUsername());
        self::assertEquals('bar', $user->getAlias());
        self::assertEquals('foo', $user->getTitle());
        self::assertEquals('https://www.example.com', $user->getAvatar());
        self::assertEquals('Karl-Heinz', $user->getEmail());
    }

    public function testHydrateUser()
    {
        $config = new LdapConfiguration([
            'active' => false,
            'connection' => [
                'host' => '1.1.1.1'
            ],
            'user' => [
                'attributes' => [
                    ['ldap_attr' => 'uid', 'user_method' => 'setUsername'],
                    ['ldap_attr' => 'foo', 'user_method' => 'setAlias'],
                    ['ldap_attr' => 'bar', 'user_method' => 'setTitle'],
                    ['ldap_attr' => 'xxxxxxxx', 'user_method' => 'setAvatar'],
                ]
            ]
        ]);

        $ldapEntry = [
            'uid' => ['Karl-Heinz'],
            'blub' => ['dfsdfsdf'],
            'foo' => ['bar'],
            'bar' => ['foo'],
            'xxxxxxxx' => ['https://www.example.com'],
            'blub1' => ['dfsdfsdf'],
        ];

        $sut = new LdapUserHydrator($config);
        $user = new User();
        $sut->hydrateUser($user, $ldapEntry);
        self::assertEquals('Karl-Heinz', $user->getUsername());
        self::assertEquals('bar', $user->getAlias());
        self::assertEquals('foo', $user->getTitle());
        self::assertEquals('https://www.example.com', $user->getAvatar());
        self::assertEquals('Karl-Heinz', $user->getEmail());
    }
}
