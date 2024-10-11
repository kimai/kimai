<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ActivateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\ActivateUserCommand
 * @group integration
 */
class ActivateUserCommandTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $userService = $container->get(UserService::class);

        $this->application->add(new ActivateUserCommand($userService));
    }

    public function testCommandName(): void
    {
        $application = $this->application;

        $command = $application->find('kimai:user:activate');
        self::assertInstanceOf(ActivateUserCommand::class, $command);
    }

    private function callCommand(?string $username): CommandTester
    {
        $command = $this->application->find('kimai:user:activate');
        $input = [
            'command' => $command->getName(),
        ];

        if ($username !== null) {
            $input['username'] = $username;
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        return $commandTester;
    }

    public function testActivate(): void
    {
        $commandTester = $this->callCommand('chris_user');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] User "chris_user" has been activated.', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('chris_user');
        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isEnabled());
    }

    public function testActivateOnActiveUser(): void
    {
        $commandTester = $this->callCommand('susan_super');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] User "susan_super" is already active.', $output);
    }

    public function testWithMissingUsername(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "username").');

        $this->callCommand(null);
    }
}
