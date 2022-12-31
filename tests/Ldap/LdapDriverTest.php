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
use App\Tests\Mocks\SystemConfigurationFactory;
use Laminas\Ldap\Exception\LdapException;
use Laminas\Ldap\Ldap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\LdapDriver
 */
class LdapDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('Laminas\Ldap\Ldap')) {
            $this->markTestSkipped('LDAP is not installed');
        }
    }

    private function getTestLdapDriver(Ldap $ldap): TestLdapDriver
    {
        $config = SystemConfigurationFactory::createStub(['ldap' => [
            'role' => [],
            'user' => [],
            'connection' => [],
        ]]);

        return new TestLdapDriver(new LdapConfiguration($config), $ldap);
    }

    public function testBindSuccess()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $zendLdap->expects($this->once())->method('bind')->willReturnSelf();

        $user = new User();
        $user->setUserIdentifier('foo');
        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->bind($user->getUserIdentifier(), 'test123');
        self::assertTrue($result);
    }

    public function testBindException()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $zendLdap->expects($this->once())->method('bind')->willThrowException(new LdapException());

        $user = new User();
        $user->setUserIdentifier('foo');
        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->bind($user->getUserIdentifier(), 'test123');
        self::assertFalse($result);
    }

    public function testSearchSuccess()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind', 'searchEntries'])->getMock();
        $zendLdap->expects($this->once())->method('bind');
        $zendLdap->expects($this->once())->method('searchEntries')->willReturn([1, 2, 3]);

        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->search('', '', []);
        self::assertEquals(['count' => 3, 1, 2, 3], $result);
    }
}

class TestLdapDriver extends LdapDriver
{
    private Ldap $testDriver;

    public function __construct(LdapConfiguration $config, Ldap $ldap)
    {
        parent::__construct($config);
        $this->testDriver = $ldap;
    }

    protected function getDriver(): Ldap
    {
        return $this->testDriver;
    }
}
