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
use App\Ldap\LdapDriver;
use App\Ldap\LdapDriverException;
use App\Ldap\LdapManager;
use App\Ldap\LdapUserHydrator;
use App\Tests\Mocks\Security\RoleServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\LdapManager
 */
class LdapManagerTest extends TestCase
{
    protected function getLdapManager(LdapDriver $driver, $roleConfig = null)
    {
        if (null === $roleConfig) {
            $roleConfig = [
                'baseDn' => 'ou=groups, dc=kimai, dc=org',
                'nameAttribute' => 'cn',
                'userDnAttribute' => 'member',
                'groups' => [
                    ['ldap_value' => 'group1', 'role' => 'ROLE_TEAMLEAD'],
                    ['ldap_value' => 'group2', 'role' => 'ROLE_ADMIN'],
                    ['ldap_value' => 'group3', 'role' => 'ROLE_CUSTOMER'], // not existing!
                    ['ldap_value' => 'group4', 'role' => 'ROLE_SUPER_ADMIN'],
                ],
            ];
        }

        $config = new LdapConfiguration([
            'user' => [
                'attributes' => [],
                'filter' => '(&(objectClass=inetOrgPerson))',
                'usernameAttribute' => 'uid',
                'attributesFilter' => '(objectClass=*)',
                'baseDn' => 'ou=users, dc=kimai, dc=org',
            ],
            'role' => $roleConfig,
        ]);

        $roles = [
            'ROLE_TEAMLEAD' => ['ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_TEAMLEAD'],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN']
        ];

        $hydrator = new LdapUserHydrator($config, (new RoleServiceFactory($this))->create($roles));

        return new LdapManager($driver, $hydrator, $config);
    }

    public function testFindUserByUsernameOnZeroResults()
    {
        $expected = [
            'count' => 0
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->once())->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            self::assertEquals('ou=users, dc=kimai, dc=org', $baseDn);
            self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foo))', $filter);

            return $expected;
        });

        $sut = $this->getLdapManager($driver);
        $actual = $sut->findUserByUsername('foo');
        self::assertNull($actual);
    }

    public function testFindUserByUsernameOnMultiResults()
    {
        $this->expectException(LdapDriverException::class);
        $this->expectExceptionMessage('This search must only return a single user');

        $expected = [
            'count' => 3
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->once())->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            self::assertEquals('ou=users, dc=kimai, dc=org', $baseDn);
            self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foo))', $filter);

            return $expected;
        });

        $sut = $this->getLdapManager($driver);
        $sut->findUserByUsername('foo');
    }

    public function testFindUserByUsernameOnValidResult()
    {
        $expected = [
            0 => ['dn' => 'foo'],
            'count' => 1,
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->once())->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            self::assertEquals('ou=users, dc=kimai, dc=org', $baseDn);
            self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foo))', $filter);

            return $expected;
        });

        $sut = $this->getLdapManager($driver);
        $actual = $sut->findUserByUsername('foo');
        self::assertInstanceOf(User::class, $actual);
    }

    public function testFindUserByOnZeroResults()
    {
        $expected = [
            'count' => 0
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->once())->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            self::assertEquals('ou=users, dc=kimai, dc=org', $baseDn);
            self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foo))', $filter);

            return $expected;
        });

        $sut = $this->getLdapManager($driver);
        $actual = $sut->findUserBy(['uid' => 'foo']);
        self::assertNull($actual);
    }

    public function testFindUserByOnMultiResults()
    {
        $this->expectException(LdapDriverException::class);
        $this->expectExceptionMessage('This search must only return a single user');

        $expected = [
            'count' => 3
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->once())->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            self::assertEquals('ou=users, dc=kimai, dc=org', $baseDn);
            self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foo))', $filter);

            return $expected;
        });

        $sut = $this->getLdapManager($driver);
        $sut->findUserBy(['uid' => 'foo']);
    }

    public function testFindUserByOnValidResult()
    {
        $expected = [
            0 => ['dn' => 'foo'],
            'count' => 1,
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->once())->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            self::assertEquals('ou=users, dc=kimai, dc=org', $baseDn);
            self::assertEquals('(&(&(objectClass=inetOrgPerson))(träl=alß#\\\aa=XY\5cZ0)(test=fu=n))', $filter);

            return $expected;
        });

        $sut = $this->getLdapManager($driver);
        $actual = $sut->findUserBy(['träl=alß#\\\aa' => 'XY\Z0', 'test' => 'fu=n']);
        self::assertInstanceOf(User::class, $actual);
    }

    public function testBind()
    {
        $user = (new User())->setUsername('foobar');

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $driver->expects($this->once())->method('bind')->willReturnCallback(function ($bindUser, $password) use ($user) {
            self::assertSame($user, $bindUser);
            self::assertEquals('a-very-secret-secret', $password);

            return true;
        });

        $sut = $this->getLdapManager($driver);
        $actual = $sut->bind($user, 'a-very-secret-secret');
        self::assertTrue($actual);
    }

    public function testUpdateUserOnZeroResults()
    {
        $user = (new User())->setUsername('foobar');
        $user->setPreferenceValue('ldap.dn', 'fooooooooooo');
        $expected = [
            [
                0 => ['dn' => 'blub'],
                'count' => 1,
            ],
            [
                'count' => 0,
            ],
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->exactly(2))->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            if ($baseDn === 'ou=users, dc=kimai, dc=org') {
                self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foobar))', $filter);

                return $expected[0];
            } elseif ($baseDn === 'blub') {
                self::assertEquals('(objectClass=*)', $filter);

                return $expected[1];
            }
            $this->fail(sprintf('Unexpected search with baseDn %s', $baseDn));
        });

        $sut = $this->getLdapManager($driver);

        $userOrig = clone $user;
        $sut->updateUser($user);
        self::assertEquals($userOrig, $user);
    }

    public function testUpdateUserOnMultiResults()
    {
        $this->expectException(LdapDriverException::class);
        $this->expectExceptionMessage('This search must only return a single user');

        $user = (new User())->setUsername('foobar');
        $user->setPreferenceValue('ldap.dn', 'xxxxxxx');

        $expected = [
            [
                0 => ['dn' => 'blub'],
                'count' => 1,
            ],
            [
                'count' => 3,
            ],
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->exactly(2))->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            if ($baseDn === 'ou=users, dc=kimai, dc=org') {
                self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foobar))', $filter);

                return $expected[0];
            } elseif ($baseDn === 'blub') {
                self::assertEquals('(objectClass=*)', $filter);

                return $expected[1];
            }

            $this->fail(sprintf('Unexpected search with baseDn %s', $baseDn));
        });

        $sut = $this->getLdapManager($driver);
        $sut->updateUser($user);
    }

    public function testUpdateUserOnValidResultWithEmptyRoleBaseDn()
    {
        $user = (new User())->setUsername('foobar');
        $user->setPreferenceValue('ldap.dn', 'sssssss');

        $expected = [
            [
                0 => ['dn' => 'blub'],
                'count' => 1,
            ],
            [
                0 => ['dn' => 'blub-updated'],
                'count' => 1,
            ],
        ];

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->exactly(2))->method('search')->willReturnCallback(function ($baseDn, $filter) use ($expected) {
            if ($baseDn === 'ou=users, dc=kimai, dc=org') {
                self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=foobar))', $filter);

                return $expected[0];
            } elseif ($baseDn === 'blub') {
                self::assertEquals('(objectClass=*)', $filter);

                return $expected[1];
            }

            $this->fail(sprintf('Unexpected search with baseDn %s', $baseDn));
        });

        $sut = $this->getLdapManager($driver, [
            'baseDn' => null,
            'nameAttribute' => 'cn',
            'userDnAttribute' => 'member',
            'groups' => [
                ['ldap_value' => 'group1', 'role' => 'ROLE_TEAMLEAD'],
                ['ldap_value' => 'group2', 'role' => 'ROLE_ADMIN'],
                ['ldap_value' => 'group3', 'role' => 'ROLE_CUSTOMER'], // not existing!
                ['ldap_value' => 'group4', 'role' => 'ROLE_SUPER_ADMIN'],
            ],
        ]);

        $userOrig = clone $user;
        $sut->updateUser($user);
        self::assertEquals($userOrig->setEmail('foobar')->setAuth(User::AUTH_LDAP), $user);
        self::assertEquals($user->getPreferenceValue('ldap.dn'), 'blub-updated');
    }

    public function getValidConfigsTestData()
    {
        return [
            [
                [
                    0 => [
                        'dn' => 'blub',
                        'uid' => ['Karl-Heinz'],
                        // just some rubbish data
                        'blub' => ['dfsdfsdf'],
                        'foo' => ['count' => 1, 'bar'],
                        'bar' => ['count' => 1, 'foo', 'xxx'],
                        'xxxxxxxx' => ['https://www.example.com'],
                        'blub1' => ['dfsdfsdf'],
                    ],
                    'count' => 1,
                ],
                [
                    'baseDn' => 'ou=groups, dc=kimai, dc=org',
                    'nameAttribute' => 'cn',
                    'usernameAttribute' => 'cn', // test that "cn" is not set and fallback to "dn" happens
                    'userDnAttribute' => 'member',
                    'groups' => [
                        ['ldap_value' => 'group1', 'role' => 'ROLE_TEAMLEAD'],
                        ['ldap_value' => 'group2', 'role' => 'ROLE_ADMIN'],
                        ['ldap_value' => 'group3', 'role' => 'ROLE_CUSTOMER'], // not existing!
                        ['ldap_value' => 'group4', 'role' => 'ROLE_SUPER_ADMIN'],
                    ],
                ],
                '(&(member=blub))'
            ],
            [
                [
                    0 => [
                        'dn' => 'blub',
                        'uid' => ['Karl-Heinz'],
                        // just some rubbish data
                        'blub' => ['foo'],
                        'foo' => ['count' => 1, 'bar'],
                        'bar' => ['count' => 1, 'foo', 'xxx'],
                        'xxxxxxxx' => ['https://www.example.com'],
                        'blub1' => ['foo(bar)'],
                    ],
                    'count' => 1,
                ],
                [
                    'baseDn' => 'ou=groups, dc=kimai, dc=org',
                    'nameAttribute' => 'cn',
                    'usernameAttribute' => 'blub1',
                    'userDnAttribute' => 'memberuid',
                    'groups' => [
                        ['ldap_value' => 'group1', 'role' => 'ROLE_TEAMLEAD'],
                        ['ldap_value' => 'group2', 'role' => 'ROLE_ADMIN'],
                        ['ldap_value' => 'group3', 'role' => 'ROLE_CUSTOMER'], // not existing!
                        ['ldap_value' => 'group4', 'role' => 'ROLE_SUPER_ADMIN'],
                    ],
                ],
                '(&(memberuid=foo\28bar\29))'
            ],
        ];
    }

    /**
     * @dataProvider getValidConfigsTestData
     */
    public function testUpdateUserOnValidResultWithRolesResult(array $expectedUsers, array $groupConfig, string $expectedGroupQuery)
    {
        $expected = [
            0 => ['dn' => 'blub'],
            'count' => 1,
        ];

        $expectedGroups = [
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

        $driver = $this->getMockBuilder(LdapDriver::class)->disableOriginalConstructor()->onlyMethods(['search'])->getMock();
        $driver->expects($this->exactly(3))->method('search')->willReturnCallback(function ($baseDn, $filter, $attributes) use ($expectedUsers, $expectedGroups, $expectedGroupQuery, $expected) {
            if ($baseDn === 'ou=users, dc=kimai, dc=org') {
                self::assertEquals('(&(&(objectClass=inetOrgPerson))(uid=Karl-Heinz))', $filter);

                return $expected;
            } elseif ($baseDn === 'blub') {
                // user attributes search
                self::assertEquals('(objectClass=*)', $filter);

                return $expectedUsers;
            } elseif ($baseDn === 'ou=groups, dc=kimai, dc=org') {
                // roles search
                self::assertEquals($expectedGroupQuery, $filter);
                self::assertEquals([0 => 'cn'], $attributes);

                return $expectedGroups;
            }
            $this->fail(sprintf('Unexpected search with baseDn %s', $baseDn));
        });

        $sut = $this->getLdapManager($driver, $groupConfig);

        $user = (new User())->setUsername('Karl-Heinz');
        $user->setPreferenceValue('ldap.dn', 'blub');
        $userOrig = clone $user;
        $userOrig->setEmail('Karl-Heinz')->setRoles(['ROLE_TEAMLEAD', 'ROLE_ADMIN'])->setAuth(User::AUTH_LDAP);

        $sut->updateUser($user);
        self::assertEquals($userOrig, $user);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
