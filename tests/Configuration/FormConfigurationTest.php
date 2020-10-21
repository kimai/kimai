<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\FormConfiguration;
use App\Entity\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\FormConfiguration
 * @covers \App\Configuration\StringAccessibleConfigTrait
 * @group legacy
 */
class FormConfigurationTest extends TestCase
{
    protected function getSut(array $settings, array $loaderSettings = [])
    {
        $loader = new TestConfigLoader($loaderSettings);

        return new FormConfiguration($loader, $settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'customer' => [
                'timezone' => 'Europe/London',
                'currency' => 'GBP',
                'country' => 'FR',
            ],
            'user' => [
                'timezone' => 'Europe/London',
                'currency' => 'GBP',
                'country' => 'FR',
                'language' => 'it',
                'theme' => 'blue',
            ],
        ];
    }

    protected function getDefaultLoaderSettings()
    {
        return [
            (new Configuration())->setName('defaults.customer.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.customer.currency')->setValue('USD'),
            (new Configuration())->setName('defaults.customer.country')->setValue('RU'),
            (new Configuration())->setName('defaults.user.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.user.currency')->setValue('USD'),
            (new Configuration())->setName('defaults.user.language')->setValue('RU'),
            (new Configuration())->setName('defaults.user.country')->setValue('RU'),
            (new Configuration())->setName('defaults.user.theme')->setValue('black'),
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('defaults', $sut->getPrefix());
    }

    public function testDefaultWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('Europe/London', $sut->getCustomerDefaultTimezone());
        $this->assertEquals('GBP', $sut->getCustomerDefaultCurrency());
        $this->assertEquals('FR', $sut->getCustomerDefaultCountry());
    }

    public function testDefaultWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('Russia/Moscov', $sut->getCustomerDefaultTimezone());
        $this->assertEquals('USD', $sut->getCustomerDefaultCurrency());
        $this->assertEquals('RU', $sut->getCustomerDefaultCountry());
        $this->assertEquals('USD', $sut->getUserDefaultCurrency());
        $this->assertEquals('RU', $sut->getUserDefaultLanguage());
        $this->assertEquals('black', $sut->getUserDefaultTheme());
        $this->assertEquals('Russia/Moscov', $sut->getUserDefaultTimezone());
        $this->assertEquals('Russia/Moscov', $sut->offsetGet('defaults.user.timezone'));
    }

    public function testDefaultWithMixedConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('defaults.customer.country')->setValue('RU'),
            (new Configuration())->setName('defaults.customer.foobar')->setValue('hello'),
        ]);
        $this->assertEquals('Europe/London', $sut->getCustomerDefaultTimezone());
        $this->assertEquals('GBP', $sut->getCustomerDefaultCurrency());
        $this->assertEquals('RU', $sut->getCustomerDefaultCountry());
    }

    public function testFindByKey()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('FR', $sut->find('customer.country'));
        $this->assertEquals('FR', $sut->find('defaults.customer.country'));
    }

    public function testUnknownConfigAreImported()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('defaults.customer.foobar')->setValue('hello'),
        ]);
        $this->assertTrue($sut->has('customer.foobar'));
        $this->assertFalse($sut->has('xxxx.foobar'));
        $this->assertEquals('hello', $sut->find('customer.foobar'));
    }
}
