<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\TimesheetConfiguration
 * @covers \App\Configuration\StringAccessibleConfigTrait
 */
class TimesheetConfigurationTest extends TestCase
{
    /**
     * @param array $settings
     * @param array $loaderSettings
     * @return TimesheetConfiguration
     */
    protected function getSut(array $settings, array $loaderSettings = [])
    {
        $loader = new TestConfigLoader($loaderSettings);
        return new TimesheetConfiguration($loader, $settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'rules' => [
                'allow_future_times' => false,
            ],
            'duration_only' => true,
            'markdown_content' => false,
            'active_entries' => [
                'hard_limit' => 99,
                'soft_limit' => 15,
            ],
        ];
    }

    protected function getDefaultLoaderSettings()
    {
        return [
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue('1'),
            (new Configuration())->setName('timesheet.duration_only')->setValue('0'),
            (new Configuration())->setName('timesheet.markdown_content')->setValue('1'),
            (new Configuration())->setName('timesheet.active_entries.hard_limit')->setValue('7'),
            (new Configuration())->setName('timesheet.active_entries.soft_limit')->setValue('3'),
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('timesheet', $sut->getPrefix());
    }


    public function testDefaultWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals(99, $sut->getActiveEntriesHardLimit());
        $this->assertEquals(15, $sut->getActiveEntriesSoftLimit());
        $this->assertEquals(false, $sut->isAllowFutureTimes());
        $this->assertEquals(true, $sut->isDurationOnly());
        $this->assertEquals(false, $sut->isMarkdownEnabled());
    }

    public function testDefaultWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals(7, $sut->getActiveEntriesHardLimit());
        $this->assertEquals(3, $sut->getActiveEntriesSoftLimit());
        $this->assertEquals(true, $sut->isAllowFutureTimes());
        $this->assertEquals(false, $sut->isDurationOnly());
        $this->assertEquals(true, $sut->isMarkdownEnabled());
    }

    public function testDefaultWithMixedConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.duration_only')->setValue(''),
        ]);
        $this->assertEquals(false, $sut->isDurationOnly());
    }

    public function testFindByKey()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals(false, $sut->find('rules.allow_future_times'));
        $this->assertEquals(false, $sut->find('timesheet.rules.allow_future_times'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown config: foo
     */
    public function testUnknownConfigAreNotImportedAndFindingThemThrowsException()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.foo')->setValue('hello'),
        ]);
        $this->assertEquals('hello', $sut->find('foo'));
    }
}
