<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\PluginCommand;
use App\Plugin\PluginInterface;
use App\Plugin\PluginManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\PluginCommand
 * @group integration
 */
class PluginCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    public function testWithPlugins()
    {
        $plugin1 = $this->getMockBuilder(PluginInterface::class)->setMethods(['getName', 'getPath'])->getMock();
        $plugin1->expects($this->exactly(3))->method('getName')->willReturn('Test-Bundle');
        $plugin1->expects($this->once())->method('getPath')->willReturn(__DIR__);

        $plugin2 = $this->getMockBuilder(PluginInterface::class)->setMethods(['getName', 'getPath'])->getMock();
        $plugin2->expects($this->exactly(3))->method('getName')->willReturn('Another one');
        $plugin2->expects($this->once())->method('getPath')->willReturn('BundleDirectory');

        $commandTester = $this->getCommandTester([$plugin1, $plugin2], []);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(__DIR__, $output);
        $this->assertStringContainsString('BundleDirectory', $output);
        $this->assertStringContainsString('Test-Bundle', $output);
        $this->assertStringContainsString('Another one', $output);
    }

    protected function getCommandTester(array $plugins, array $options = [])
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new PluginCommand(new PluginManager($plugins)));

        $command = $this->application->find('kimai:plugins');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], $options);
        $commandTester->execute($inputs);

        return $commandTester;
    }
}
