<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\PermissionSection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\PermissionSection
 */
class PermissionSectionTest extends TestCase
{
    public function testFilter(): void
    {
        $sut = new PermissionSection('A wonderful name', '_contract');
        self::assertFalse($sut->filter('contract_settings'));
        self::assertTrue($sut->filter('my_contract'));
        self::assertTrue($sut->filter('my_contract_settings'));

        $sut = new PermissionSection('A wonderful name', ['_contract', '_settings']);
        self::assertFalse($sut->filter('contractor'));
        self::assertTrue($sut->filter('contract_settings'));
        self::assertTrue($sut->filter('my_contract_settings'));
    }
}
