<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\InstallCommand;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\InstallCommand
 * @group integration
 */
class InstallCommandTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();
        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        $this->application->add(new InstallCommand(
            $doctrine->getConnection() // @phpstan-ignore argument.type
        ));
    }

    public function testCommandName(): void
    {
        $command = $this->application->find('kimai:install');
        self::assertInstanceOf(InstallCommand::class, $command);
    }
}
