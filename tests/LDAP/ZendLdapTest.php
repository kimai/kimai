<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Configuration\LdapConfiguration;
use App\Ldap\ZendLdap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\ZendLdap
 */
class ZendLdapTest extends TestCase
{
    public function testConstructDeactivated()
    {
        $config = new LdapConfiguration([
            'active' => false,
            'connection' => [
                'host' => '1.1.1.1'
            ]
        ]);

        $sut = new ZendLdap($config);
        $options = $sut->getOptions();
        self::assertNull($options['host']);
    }

    public function testConstructActivatedPassesOptions()
    {
        $config = new LdapConfiguration([
            'active' => true,
            'connection' => [
                'host' => '1.1.1.1'
            ]
        ]);

        $sut = new ZendLdap($config);
        $options = $sut->getOptions();
        self::assertEquals('1.1.1.1', $options['host']);
    }
}
