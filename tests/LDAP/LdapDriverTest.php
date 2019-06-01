<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\User;
use App\Ldap\LdapDriver;
use PHPUnit\Framework\TestCase;
use Zend\Ldap\Exception\LdapException;
use Zend\Ldap\Ldap;

/**
 * @covers \App\Ldap\LdapDriver
 */
class LdapDriverTest extends TestCase
{
    public function testBindSuccess()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->setMethods(['bind'])->getMock();
        $zendLdap->expects($this->once())->method('bind')->willReturnSelf();

        $user = new User();
        $sut = new LdapDriver($zendLdap);
        $result = $sut->bind($user, 'test123');
        self::assertTrue($result);
    }

    public function testBindException()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->setMethods(['bind'])->getMock();
        $zendLdap->expects($this->once())->method('bind')->willThrowException(new LdapException());

        $user = new User();
        $sut = new LdapDriver($zendLdap);
        $result = $sut->bind($user, 'test123');
        self::assertFalse($result);
    }

    public function testSearchSuccess()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->setMethods(['searchEntries'])->getMock();
        $zendLdap->expects($this->once())->method('searchEntries')->willReturn([1, 2, 3]);

        $sut = new LdapDriver($zendLdap);
        $result = $sut->search('', '', []);
        self::assertEquals(['count' => 3, 1, 2, 3], $result);
    }

    /**
     * @expectedException \App\Ldap\LdapDriverException
     * @expectedExceptionMessage An error occurred with the search operation.
     */
    public function testSearchException()
    {
        $zendLdap = $this->getMockBuilder(Ldap::class)->disableOriginalConstructor()->setMethods(['searchEntries'])->getMock();
        $zendLdap->expects($this->once())->method('searchEntries')->willThrowException(
            new LdapException($zendLdap, '', LdapException::LDAP_SERVER_DOWN)
        );

        $sut = new LdapDriver($zendLdap);
        $sut->search('', '', []);
    }
}
