<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Configuration\LdapConfiguration;
use App\Entity\User;
use App\Ldap\LdapUserHydrator;
use App\Security\RoleService;
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
            ],
            'role' => [],
        ]);

        $sut = new LdapUserHydrator($config, new RoleService([]));
        $user = $sut->hydrate(['dn' => 'blub',]);
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
                    ['ldap_attr' => 'roles', 'user_method' => 'setRoles'],
                    ['ldap_attr' => 'xxxxxxxx', 'user_method' => 'setAvatar'],
                    ['ldap_attr' => 'blubXX', 'user_method' => 'setAvatar'],
                ]
            ],
            'role' => [],
        ]);

        $ldapEntry = [
            'uid' => ['Karl-Heinz'],
            'blub' => ['dfsdfsdf'],
            'foo' => ['count' => 1, 0 => 'bar'],
            'bar' => ['foo'],
            'roles' => ['count' => 2, 0 => 'ROLE_TEAMLEAD', 1 => 'ROLE_ADMIN'],
            'xxxxxxxx' => ['https://www.example.com'],
            'blub1' => ['dfsdfsdf'],
            'dn' => 'blub',
        ];

        $sut = new LdapUserHydrator($config, new RoleService([]));
        $user = $sut->hydrate($ldapEntry);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('Karl-Heinz', $user->getUsername());
        self::assertEquals('bar', $user->getAlias());
        self::assertEquals('foo', $user->getTitle());
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
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
            ],
            'role' => [],
        ]);

        $ldapEntry = [
            'uid' => ['Karl-Heinz'],
            'blub' => ['dfsdfsdf'],
            'foo' => ['bar'],
            'bar' => ['foo'],
            'xxxxxxxx' => ['https://www.example.com'],
            'blub1' => ['dfsdfsdf'],
            'dn' => 'blub',
        ];

        $sut = new LdapUserHydrator($config, new RoleService([]));
        $user = new User();
        $sut->hydrateUser($user, $ldapEntry);
        self::assertEquals('Karl-Heinz', $user->getUsername());
        self::assertEquals('bar', $user->getAlias());
        self::assertEquals('foo', $user->getTitle());
        self::assertEquals('https://www.example.com', $user->getAvatar());
        self::assertEquals('Karl-Heinz', $user->getEmail());
    }

    public function testHydrateRoles()
    {
        $config = new LdapConfiguration([
            'user' => [
                'attributes' => []
            ],
            'role' => [
                'nameAttribute' => 'cn',
                'userDnAttribute' => 'member',
                'groups' => [
                    ['ldap_value' => 'group1', 'role' => 'ROLE_TEAMLEAD'],
                    ['ldap_value' => 'group2', 'role' => 'ROLE_ADMIN'],
                    ['ldap_value' => 'group3', 'role' => 'ROLE_CUSTOMER'], // not existing!
                    ['ldap_value' => 'group4', 'role' => 'ROLE_SUPER_ADMIN'],
                ],
            ],
        ]);

        $ldapGroups = [
            // ROLE_TEAMLEAD
            0 => [
                'cn' => [0 => 'group1'],
                'member' => [0 => 'uid=user1,ou=users,dc=kimai,dc=org', 1 => 'uid=user2,ou=users,dc=kimai,dc=org'],
            ],
            // ROLE_ADMIN
            1 => [
                'cn' => [0 => 'admin'],
                'member' => [0 => 'uid=user2,ou=users,dc=kimai,dc=org', 1 => 'uid=user3,ou=users,dc=kimai,dc=org'],
            ],
            // will be ignored: unknown group
            2 => [
                'cn' => [0 => 'kimai_admin'],
                'member' => [0 => 'uid=user2,ou=users,dc=kimai,dc=org', 1 => 'uid=user3,ou=users,dc=kimai,dc=org'],
            ],
            // will be ignored: unknown group
            3 => [
                'cn' => [0 => 'group3'],
                'member' => [0 => 'uid=user2,ou=users,dc=kimai,dc=org', 1 => 'uid=user3,ou=users,dc=kimai,dc=org'],
            ],
            // will be ignored: the counter below does not announce this group!
            4 => [
                'cn' => [0 => 'group4'],
                'member' => [0 => 'uid=user2,ou=users,dc=kimai,dc=org', 1 => 'uid=user3,ou=users,dc=kimai,dc=org'],
            ],
            'count' => 4
        ];

        $sut = new LdapUserHydrator($config, new RoleService([
            'ROLE_TEAMLEAD' => ['ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_TEAMLEAD'],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN']
        ]));
        $user = new User();
        $sut->hydrateRoles($user, $ldapGroups);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
