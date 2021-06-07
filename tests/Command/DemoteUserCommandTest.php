<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\DemoteUserCommand;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\AbstractRoleCommand
 * @covers \App\Command\DemoteUserCommand
 * @group integration
 */
class DemoteUserCommandTest extends KernelTestCase
{
    public function testCommandName()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new DemoteUserCommand($this->createMock(UserService::class)));

        $command = $application->find('kimai:user:demote');
        self::assertInstanceOf(DemoteUserCommand::class, $command);

        // test alias
        $command = $application->find('fos:user:demote');
        self::assertInstanceOf(DemoteUserCommand::class, $command);
    }
}
