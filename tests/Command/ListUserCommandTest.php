<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ListUserCommand;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\ListUserCommand
 * @group integration
 */
class ListUserCommandTest extends KernelTestCase
{
    public function testWithPlugins(): void
    {
        $commandTester = $this->getCommandTester();
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Username   Email   Roles   Active   Authenticator', $output);
        self::assertStringContainsString('---------- ------- ------- -------- -------------', $output);
    }

    protected function getCommandTester(): CommandTester
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findAll')->willReturn([]);

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new ListUserCommand($repository));

        $command = $application->find('kimai:user:list');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], []);
        $commandTester->execute($inputs);

        return $commandTester;
    }
}
