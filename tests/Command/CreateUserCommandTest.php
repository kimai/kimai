<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\User\UserService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\CreateUserCommand
 * @group integration
 */
class CreateUserCommandTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();
        /** @var UserService $userService */
        $userService = $container->get(UserService::class);
        $this->application->add(new CreateUserCommand($userService));
    }

    public function testCreateUserFailsForShortPassword(): void
    {
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar');

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] plainPassword: This value is too short.', $output);
    }

    public function testCreateUser(): void
    {
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar12');

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[OK] Success! Created user: MyTestUser', $output);

        $container = self::$kernel->getContainer();
        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var UserRepository $userRepository */
        $userRepository = $doctrine->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('MyTestUser');
        self::assertInstanceOf(User::class, $user);
    }

    protected function createUser($username, $email, $role, $password): CommandTester
    {
        $command = $this->application->find('kimai:user:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'password' => $password
        ]);

        return $commandTester;
    }

    public function testUserWithEmptyFieldsTriggersValidationProblem(): void
    {
        $commandTester = $this->createUser('xx', '', 'ROLE_USER', '');
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] email: This value should not be blank', $output);
        self::assertStringContainsString('[ERROR] plainPassword: This value should not be blank', $output);
        self::assertStringContainsString('[ERROR] plainPassword: This value is too short.', $output);
    }

    public function testUserAlreadyExisting(): void
    {
        $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar123');
        $commandTester = $this->createUser('MyTestUser', 'user2@example.com', 'ROLE_USER', 'foobar123');

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] username: The username is already used.', $output);
    }

    public function testEmailAlreadyExisting(): void
    {
        $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar12');
        $commandTester = $this->createUser('MyTestUser2', 'user@example.com', 'ROLE_USER', 'foobar');

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] email: This e-mail address is already in use.', $output);
    }

    public function testUserEmail(): void
    {
        $commandTester = $this->createUser('MyTestUser', 'ROLE_USER', 'ROLE_USER', 'foobar12');

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] email: This value is not a valid email address', $output);
    }
}
