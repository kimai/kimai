<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\InstallCommand;
use App\Constants;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\InstallCommand
 * @group integration
 */
class InstallCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function getCommand(): Command
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $this->application->add(new InstallCommand(
            $container->getParameter('kernel.project_dir'),
            $container->get('doctrine')->getConnection()
        ));

        return $this->application->find('kimai:install');
    }

    public function testFullRunWithEverythingPreInstalled()
    {
        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Kimai installation running', $result);
        // create database is skipped
        self::assertStringContainsString('[NOTE] Database is existing and connection could be established', $result);
        // make sure migrations run always
        self::assertStringContainsString('Application Migrations', $result);
        self::assertStringContainsString('No migrations to execute.', $result);

        self::assertStringContainsString(
            sprintf('[OK] Congratulations! Successfully installed Kimai 2 version %s (%s)', Constants::VERSION, Constants::STATUS),
            $result
        );

        self::assertEquals(0, $commandTester->getStatusCode());
    }
}
