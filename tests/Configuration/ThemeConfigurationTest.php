<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\SystemConfiguration;
use App\Configuration\ThemeConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\ThemeConfiguration
 * @covers \App\Configuration\SystemConfiguration
 */
class ThemeConfigurationTest extends TestCase
{
    protected function getSut(array $settings, array $loaderSettings = []): ThemeConfiguration
    {
        $loader = new TestConfigLoader($loaderSettings);
        $config = new SystemConfiguration($loader, ['theme' => $settings]);

        return new ThemeConfiguration($config);
    }

    /**
     * @return array
     */
    protected function getDefaultSettings()
    {
        return [
            'show_about' => true,
            'chart' => [
                'background_color' => 'rgba(0,115,183,0.7)',
                'border_color' => '#3b8bba',
                'grid_color' => 'rgba(0,0,0,.05)',
                'height' => '200'
            ],
            'branding' => [
                'logo' => 'Logooooo',
                'mini' => 'Mini2',
                'company' => 'Super Kimai',
                'title' => null,
            ],
        ];
    }

    public function testConfig()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('Super Kimai', $sut->offsetGet('branding.company'));
        $this->assertEquals('Logooooo', $sut->offsetGet('branding.logo'));
        $this->assertEquals('Mini2', $sut->offsetGet('branding.mini'));

        self::assertTrue($sut->offsetExists('branding.mini'));
        self::assertFalse($sut->offsetExists('xxxx.yyyyy'));
    }

    public function testOffsetSetThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('ThemeConfiguration does not support offsetSet()');

        $sut = $this->getSut($this->getDefaultSettings(), []);
        $sut->offsetSet('dfsdf', 'sdfsdf');
    }

    public function testOffsetUnsetThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('ThemeConfiguration does not support offsetUnset()');

        $sut = $this->getSut($this->getDefaultSettings(), []);
        $sut->offsetUnset('dfsdf');
    }
}
