<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ChangePasswordCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\ChangePasswordCommand
 * @covers \App\Command\AbstractUserCommand
 * @group integration
 */
class ChangePasswordCommandTest extends KernelTestCase
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

        $this->application->add(new ChangePasswordCommand($userService));
    }

    public function testCommandName()
    {
        $application = $this->application;

        $command = $application->find('kimai:user:password');
        self::assertInstanceOf(ChangePasswordCommand::class, $command);

        // test alias
        $command = $application->find('fos:user:change-password');
        self::assertInstanceOf(ChangePasswordCommand::class, $command);
    }

    protected function callCommand(?string $username, ?string $password)
    {
        $command = $this->application->find('kimai:user:password');
        $input = [
            'command' => $command->getName(),
        ];
        $interactive = false;

        if ($username !== null) {
            $input['username'] = $username;
        }

        if ($password !== null) {
            $input['password'] = $password;
        } else {
            $interactive = true;
        }

        $commandTester = new CommandTester($command);

        $options = [];
        if ($interactive) {
            $options = ['interactive' => true];
            $commandTester->setInputs(['12345678']);
        }

        $commandTester->execute($input, $options);

        return $commandTester;
    }

    public function testChangePassword()
    {
        $commandTester = $this->callCommand('john_user', '0987654321');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Changed password for user "john_user".', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByUsername('john_user');
        self::assertInstanceOf(User::class, $user);

        $container = self::$kernel->getContainer();
        $encoderService = $container->get('security.password_encoder');
        self::assertTrue($encoderService->isPasswordValid($user, '0987654321'));
    }

    public function testWithMissingUsername()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "username").');

        $this->callCommand(null, '1234567890');
    }

    public function testWithMissingPasswordAsksForPassword()
    {
        $commandTester = $this->callCommand('john_user', null);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Changed password for user "john_user".', $output);
    }
}
