<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\CreateReleaseCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\CreateReleaseCommand
 * @group integration
 */
class CreateReleaseCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new CreateReleaseCommand(realpath(__DIR__ . '/../../')));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:create-release');
        self::assertInstanceOf(CreateReleaseCommand::class, $command);
    }
}
