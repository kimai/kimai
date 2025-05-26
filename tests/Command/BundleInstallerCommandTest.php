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
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\AbstractBundleInstallerCommand
 * @group integration
 */
class BundleInstallerCommandTest extends KernelTestCase
{
    private Application $application;

    /**
     * @param class-string $className
     */
    private function getCommand(string $className): Command
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new $className());

        return $this->application->find('kimai:bundle:test:install');
    }

    public function testFullRun(): void
    {
        $command = $this->getCommand(TestBundleInstallerCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Starting installation of plugin: Test', $result);
        self::assertStringContainsString('[OK] Congratulations! Plugin was successful installed: Test', $result);
        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testMissingMigrationThrowsException(): void
    {
        $command = $this->getCommand(InstallerWithMissingMigrationsCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Failed to install database for bundle TestBundle.', $result);
        self::assertEquals(1, $commandTester->getStatusCode());
    }

    public function testInvalidNamespaceWillRaiseException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unsupported namespace given, expected "KimaiPlugin" but received "App". Please overwrite getBundleName() and return the correct bundle name.');

        $command = $this->getCommand(InvalidNamespaceTestBundleInstallerCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testAssetsInstallIsOk(): void
    {
        $command = $this->getCommand(InstallerWithAssetsCommand::class);

        $this->application->add(new FakeCommand('assets:install', 0, null));

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Command assets:install was executed successfully :-)', $result);
        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testAssetsInstallReturnsNonZeroExitCode(): void
    {
        $command = $this->getCommand(InstallerWithAssetsCommand::class);

        $this->application->add(new FakeCommand('assets:install', 1, null));

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Failed to install assets for bundle TestBundle.', $result);
        self::assertEquals(1, $commandTester->getStatusCode());
    }
}

class FakeCommand extends Command
{
    public function __construct(string $commandName, private int $exitCode, private ?string $exception = null)
    {
        parent::__construct($commandName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null !== $this->exception) {
            throw new \Exception($this->exception);
        }

        if ($this->exitCode === 0) {
            $output->write('Command ' . $this->getName() . ' was executed successfully :-)');
        }

        return $this->exitCode;
    }
}

class InvalidNamespaceTestBundleInstallerCommand extends AbstractBundleInstallerCommand
{
    protected function getBundleCommandNamePart(): string
    {
        return 'test';
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

class InstallerWithMissingMigrationsCommand extends TestBundleInstallerCommand
{
    protected function getMigrationConfigFilename(): ?string
    {
        return __DIR__ . '/sdfsdfsdfsdf';
    }
}

class InstallerWithAssetsCommand extends TestBundleInstallerCommand
{
    protected function hasAssets(): bool
    {
        return true;
    }
}
