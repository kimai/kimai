<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\PluginCommand;
use App\Plugin\PackageManager;
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
    private Application $application;

    public function testWithPlugins(): void
    {
        $plugin1 = $this->getMockBuilder(PluginInterface::class)->onlyMethods(['getName', 'getPath'])->getMock();
        $plugin1->expects($this->any())->method('getName')->willReturn('TestBundle');
        $plugin1->expects($this->exactly(2))->method('getPath')->willReturn(__DIR__ . '/../Plugin/Fixtures/TestPlugin');

        $commandTester = $this->getCommandTester([$plugin1], []);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString(__DIR__, $output);
        self::assertStringContainsString('Plugin/Fixtures/TestPlugin', $output);
        self::assertStringContainsString('TestPlugin from composer.json', $output);
    }

    private function getCommandTester(array $plugins, array $options = []): CommandTester
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new PluginCommand(new PluginManager($plugins), new PackageManager(__DIR__ . '/../../')));

        $command = $this->application->find('kimai:plugins');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], $options);
        $commandTester->execute($inputs);

        return $commandTester;
    }
}
