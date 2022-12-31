<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\InstallCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $this->application->add(new InstallCommand(
            $container->get('doctrine')->getConnection(),
            $this->application->getKernel()->getEnvironment()
        ));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:install');
        self::assertInstanceOf(InstallCommand::class, $command);
    }
}
