<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Configuration\ThemeConfiguration;
use App\Tests\Configuration\TestConfigLoader;
use App\Twig\ConfigExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\ConfigExtension
 */
class ConfigExtensionTest extends TestCase
{
    protected function getSut(array $settings, array $loaderSettings = []): ConfigExtension
    {
        $loader = new TestConfigLoader($loaderSettings);
        $config = new ThemeConfiguration($loader, $settings);

        return new ConfigExtension($config);
    }

    public function testGetFunctions()
    {
        $functions = ['theme_config'];
        $sut = $this->getSut([], []);
        $twigFunctions = $sut->getFunctions();
        self::assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            self::assertInstanceOf(TwigFunction::class, $filter);
            self::assertEquals($functions[$i++], $filter->getName());
        }
    }

    private function getDefaultSettings(): array
    {
        return [
            'active_warning' => 3,
            'box_color' => 'green',
            'select_type' => null,
            'show_about' => true,
            'chart' => [
                'background_color' => 'rgba(0,115,183,0.7)',
                'border_color' => '#3b8bba',
                'grid_color' => 'rgba(0,0,0,.05)',
                'height' => '200'
            ],
            'branding' => [
                'logo' => null,
                'mini' => null,
                'company' => null,
                'title' => null,
            ],
            'auto_reload_datatable' => false,
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        self::assertFalse($sut->getThemeConfig('auto_reload_datatable'));
        self::assertEquals(3, $sut->getThemeConfig('active_warning'));
        self::assertEquals('green', $sut->getThemeConfig('box_color'));
    }
}
