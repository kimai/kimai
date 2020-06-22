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
        ];
    }

    protected function getDefaultLoaderSettings()
    {
        return [
            (new Configuration())->setName('defaults.customer.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.customer.currency')->setValue('USD'),
            (new Configuration())->setName('defaults.customer.country')->setValue('RU'),
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

    public function testUnknownConfigAreNotImportedAndFindingThemThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown config: foobar');

        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('defaults.customer.foobar')->setValue('hello'),
        ]);
        $this->assertEquals('hello', $sut->find('customer.foobar'));
    }
}
