<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Command\CreateUserCommand;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @coversDefaultClass \App\Command\CreateUserCommand
 * @group integration
 */
class CreateUserCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $passwordEncoder = $container->get('security.password_encoder');

        $validationResult = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();
        $validationResult->method('count')->willReturn(0);
        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->method('validate')->willReturn($validationResult);

        $this->application->add(new CreateUserCommand(
            $passwordEncoder,
            $container->get('doctrine'),
            $validator
        ));
    }

    public function testCreateUser()
    {
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar');

        $output = $commandTester->getDisplay();
        $this->assertContains('[OK] Success! Created user: MyTestUser', $output);

        $container = self::$kernel->getContainer();
        $user = $container->get('doctrine')->getRepository(User::class)->loadUserByUsername('MyTestUser');
        $this->assertNotNull($user);
    }

    protected function createUser($username, $email, $role, $password)
    {

        $command = $this->application->find('kimai:create-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'password' => $password
        ));

        return $commandTester;
    }

    public function testUserAlreadyExisting()
    {
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar');
        $commandTester = $this->createUser('MyTestUser', 'user@example.com', 'ROLE_USER', 'foobar');

        $output = $commandTester->getDisplay();
        $this->assertContains('[ERROR] Failed to create user: MyTestUser', $output);
    }
}
