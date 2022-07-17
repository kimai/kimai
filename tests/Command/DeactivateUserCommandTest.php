<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\DeactivateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\DeactivateUserCommand
 * @group integration
 */
class DeactivateUserCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    private $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $userService = $container->get(UserService::class);

        $this->application->add(new DeactivateUserCommand($userService));
    }

    public function testCommandName()
    {
        $application = $this->application;

        $command = $application->find('kimai:user:deactivate');
        self::assertInstanceOf(DeactivateUserCommand::class, $command);
    }

    protected function callCommand(?string $username)
    {
        $command = $this->application->find('kimai:user:deactivate');
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

    public function testDeactivate()
    {
        $commandTester = $this->callCommand('john_user');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] User "john_user" has been deactivated.', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('john_user');
        self::assertInstanceOf(User::class, $user);
        self::assertFalse($user->isEnabled());
    }

    public function testDeactivateOnDeactivatedUser()
    {
        $commandTester = $this->callCommand('chris_user');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] User "chris_user" is already deactivated.', $output);
    }

    public function testWithMissingUsername()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "username").');

        $this->callCommand(null);
    }
}
