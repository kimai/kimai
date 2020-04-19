<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\IconExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

/**
 * @covers \App\Twig\IconExtension
 */
class IconExtensionTest extends TestCase
{
    public function testGetFilters()
    {
        $filters = ['icon'];
        $sut = new IconExtension();
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($filters), $twigFilters);
        $i = 0;
        /** @var TwigFilter $filter */
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testIcon()
    {
        $icons = [
            'user', 'customer', 'project', 'activity', 'admin', 'invoice', 'timesheet', 'dashboard', 'logout', 'trash',
            'delete', 'repeat', 'edit', 'manual', 'help', 'start', 'start-small', 'stop', 'stop-small', 'filter',
            'create', 'list', 'print', 'visibility', 'calendar', 'money', 'duration', 'download', 'copy', 'settings',
            'export', 'pdf', 'csv', 'ods', 'xlsx', 'on', 'off', 'audit', 'home', 'shop', 'about', 'debug', 'profile-stats',
            'profile', 'warning', 'permissions', 'back', 'tag', 'avatar', 'timesheet-team', 'plugin', 'configuration'
        ];

        // test pre-defined icons
        $sut = new IconExtension();

        foreach ($icons as $icon) {
            $result = $sut->icon($icon);
            $this->assertNotEmpty($result, 'Missing icon definition: ' . $icon);
            $this->assertIsString($result);
        }

        // test fallback will be returned
        $this->assertEquals('', $sut->icon('foo'));
        $this->assertEquals('bar', $sut->icon('foo', 'bar'));
    }
}
