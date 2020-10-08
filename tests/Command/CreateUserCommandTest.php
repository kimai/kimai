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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\CreateUserCommand
 * @group integration
 */
class CreateUserCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $passwordEncoder = $container->get('security.password_encoder');

        $this->application->add(new CreateUserCommand(
            $passwordEncoder,
            $container->get('doctrine'),
            $container->get('validator')
        ));
    }

    public function testCreateUserFailsForShortPassword()
    {
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] plainPassword (foobar)', $output);
        $this->assertStringContainsString('This value is too short. It should have 8 characters or more.', $output);
    }

    public function testCreateUser()
    {
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar12');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Success! Created user: MyTestUser', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByUsername('MyTestUser');
        self::assertInstanceOf(User::class, $user);
        self::assertNotNull($user);
    }

    protected function createUser($username, $email, $role, $password)
    {
        $command = $this->application->find('kimai:create-user');
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

    public function testUserWithEmptyFieldsTriggersValidationProblem()
    {
        $commandTester = $this->createUser('xx', '', 'ROLE_USER', '');
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] email ()', $output);
        $this->assertStringContainsString('Please enter an email', $output);
        $this->assertStringContainsString('[ERROR] plainPassword ()', $output);
        $this->assertStringContainsString('Please enter a password', $output);
    }

    public function testUserAlreadyExisting()
    {
        $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar12');
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] username (mytestuser)', $output);
        $this->assertStringContainsString('The username is already used', $output);
    }

    public function testUserEmail()
    {
        $commandTester = $this->createUser('MyTestUser', 'ROLE_USER', 'ROLE_USER', 'foobar12');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] email (ROLE_USER)', $output);
        $this->assertStringContainsString('The email is not valid', $output);
    }
}
