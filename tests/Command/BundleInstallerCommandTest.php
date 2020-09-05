<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\AbstractBundleInstallerCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\AbstractBundleInstallerCommand
 * @group integration
 */
class BundleInstallerCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function getCommand(string $className): Command
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $this->application->add(new $className(
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('kimai.plugin_dir')
        ));

        return $this->application->find('kimai:bundle:test:install');
    }

    public function testFullRun()
    {
        $command = $this->getCommand(TestBundleInstallerCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Starting installation of plugin: Test', $result);
        self::assertStringContainsString('[OK] Congratulations! Plugin was successful installed: Test', $result);
        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testMissingMigrationThrowsException()
    {
        $command = $this->getCommand(MissingBundleInstallerCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Failed to install database for bundle TestBundle. Missing doctrine migrations config file:', $result);
        self::assertEquals(1, $commandTester->getStatusCode());
    }

    public function testAssetsInstallationFailure()
    {
        $command = $this->getCommand(AssetsInstallerFailureCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Failed to install assets for bundle TestBundle. Problem occurred while installing assets.', $result);
        self::assertEquals(1, $commandTester->getStatusCode());
    }
}

class TestBundleInstallerCommand extends AbstractBundleInstallerCommand
{
    protected function getBundleCommandNamePart(): string
    {
        return 'test';
    }

    protected function getBundleName(): string
    {
        return 'TestBundle';
    }
}

class MissingBundleInstallerCommand extends TestBundleInstallerCommand
{
    protected function getMigrationsFilename(): ?string
    {
        return __DIR__ . '/sdfsdfsdfsdf';
    }
}

class AssetsInstallerFailureCommand extends TestBundleInstallerCommand
{
    protected function hasAssets(): bool
    {
        return true;
    }

    protected function installAssets(SymfonyStyle $io, OutputInterface $output)
    {
        throw new \Exception('Problem occurred while installing assets.');
    }
}
