<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ActivateUserCommand;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\ActivateUserCommand
 * @group integration
 */
class ActivateUserCommandTest extends KernelTestCase
{
    public function testCommandName()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new ActivateUserCommand($this->createMock(UserService::class)));

        $command = $application->find('kimai:user:activate');
        self::assertInstanceOf(ActivateUserCommand::class, $command);

        // test alias
        $command = $application->find('fos:user:activate');
        self::assertInstanceOf(ActivateUserCommand::class, $command);
    }
}
