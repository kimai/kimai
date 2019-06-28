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
use App\Utils\File;
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

    protected function getCommand($permission = 0777): Command
    {
        $fileMock = $this->getMockBuilder(File::class)->setMethods(['getPermissions'])->getMock();
        $fileMock->expects($this->exactly(5))->method('getPermissions')->willReturn($permission);

        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $this->application->add(new InstallCommand(
            $container->getParameter('kernel.project_dir'),
            $container->get('doctrine')->getConnection(),
            $fileMock
        ));

        return $this->application->find('kimai:install');
    }

    public function testMissingPermissionsAborted()
    {
        $command = $this->getCommand(0210);
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $result = $commandTester->getDisplay();

        self::assertContains('var/cache/', $result);
        self::assertContains('var/data/', $result);
        self::assertContains('var/log/', $result);
        self::assertContains('var/plugins/', $result);
        self::assertContains('var/sessions/', $result);
        self::assertEquals(5, substr_count($result, 'missing: read owner,read group,write group'));
        self::assertContains('[WARNING] Aborting installation to review the permissions for above mentioned', $result);
        self::assertEquals(InstallCommand::ERROR_PERMISSIONS, $commandTester->getStatusCode());
    }

    public function testFullRunWithEverythingPreInstalled()
    {
        $command = $this->getCommand(0770);
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $result = $commandTester->getDisplay();

        // create database is skipped
        self::assertContains('[NOTE] Database is existing and connection could be established', $result);

        // create schema is skipped
        self::assertContains('[NOTE] It seems as if you already have the required tables in your database,', $result);
        self::assertContains('skipping schema creation', $result);

        self::assertContains('[NOTE] Found ', $result);
        self::assertContains(' migrations in your database, skipping import', $result);

        self::assertContains(
            sprintf('[OK] Congratulations! Kimai 2 (%s %s) was successful installed!', Constants::VERSION, Constants::STATUS),
            $result
        );

        self::assertEquals(0, $commandTester->getStatusCode());
    }
}
