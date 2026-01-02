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
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Tests\Mocks\TestLogger;
use Laminas\Ldap\Exception\LdapException;
use Laminas\Ldap\Ldap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

#[CoversClass(LdapDriver::class)]
class LdapDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Ldap::class)) {
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

    public function testBindSuccess(): void
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $zendLdap->expects($this->once())->method('bind')->willReturnSelf();

        $user = new User();
        $user->setUserIdentifier('foo');
        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->bind($user->getUserIdentifier(), 'test123');
        self::assertTrue($result);
    }

    public function testBindException(): void
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $zendLdap->expects($this->once())->method('bind')->willThrowException(new LdapException());

        $user = new User();
        $user->setUserIdentifier('foo');
        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->bind($user->getUserIdentifier(), 'test123');
        self::assertFalse($result);

        $logs = $sut->getLogger()->cleanLogs();
        self::assertCount(2, $logs);
        self::assertEquals(LogLevel::ERROR, $logs[1][0]);
        self::assertStringStartsWith('Failed binding to LDAP at', $logs[1][1]);
    }

    public function testSearchSuccess(): void
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind', 'searchEntries'])->getMock();
        $zendLdap->expects($this->once())->method('bind');
        $zendLdap->expects($this->once())->method('searchEntries')->willReturn([1, 2, 3]);

        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->search('', '', []);
        self::assertEquals(['count' => 3, 1, 2, 3], $result);
    }

    public function testSearchFailure(): void
    {
        $this->expectException(LdapDriverException::class);
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->onlyMethods(['bind', 'searchEntries'])->getMock();
        $zendLdap->expects($this->once())->method('bind');
        $zendLdap->expects($this->once())->method('searchEntries')->willThrowException(new LdapException());

        $sut = $this->getTestLdapDriver($zendLdap);
        $result = $sut->search('', '', []);
        self::assertEquals(['count' => 3, 1, 2, 3], $result);

        $logs = $sut->getLogger()->cleanLogs();
        self::assertCount(2, $logs);
        self::assertEquals(LogLevel::DEBUG, $logs[0][0]);
        self::assertStringStartsWith('Failed to search LDAP', $logs[1][1]);
        self::assertEquals(LogLevel::ERROR, $logs[1][0]);
        self::assertStringStartsWith('An error occurred with the search operation', $logs[1][1]);
    }
}

class TestLdapDriver extends LdapDriver
{
    private Ldap $testDriver;
    public TestLogger $logger;

    public function __construct(LdapConfiguration $config, Ldap $ldap)
    {
        $this->logger = new TestLogger();
        parent::__construct($config, $this->logger);
        $this->testDriver = $ldap;
    }

    protected function getDriver(): Ldap
    {
        return $this->testDriver;
    }

    public function getLogger(): TestLogger
    {
        return $this->logger;
    }
}
