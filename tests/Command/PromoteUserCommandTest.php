<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\PromoteUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\AbstractRoleCommand
 * @covers \App\Command\PromoteUserCommand
 * @group integration
 */
class PromoteUserCommandTest extends KernelTestCase
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

        $this->application->add(new PromoteUserCommand($userService));
    }

    public function testCommandName()
    {
        $application = $this->application;

        $command = $application->find('kimai:user:promote');
        self::assertInstanceOf(PromoteUserCommand::class, $command);
    }

    protected function callCommand(?string $username, ?string $role, bool $super = false)
    {
        $command = $this->application->find('kimai:user:promote');
        $input = [
            'command' => $command->getName(),
        ];

        if ($role !== null) {
            $input['role'] = $role;
        }

        if ($username !== null) {
            $input['username'] = $username;
        }

        if ($super) {
            $input['--super'] = true;
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        return $commandTester;
    }

    public function testPromoteRole()
    {
        $commandTester = $this->callCommand('john_user', 'ROLE_TEAMLEAD');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Role "ROLE_TEAMLEAD" has been added to user "john_user".', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('john_user');
        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->hasTeamleadRole());
    }

    public function testPromoteSuper()
    {
        $commandTester = $this->callCommand('john_user', null, true);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] User "john_user" has been promoted as a super administrator.', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('john_user');
        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isSuperAdmin());
    }

    public function testPromoteSuperFailsOnSuperAdmin()
    {
        $commandTester = $this->callCommand('susan_super', null, true);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] User "susan_super" does already have the super administrator role.', $output);
    }

    public function testPromoteTeamleadFailsOnTeamlead()
    {
        $commandTester = $this->callCommand('tony_teamlead', 'ROLE_TEAMLEAD', false);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] User "tony_teamlead" did already have "ROLE_TEAMLEAD" role.', $output);
    }

    public function testPromoteRoleAndSuperFails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can pass either the role or the --super option (but not both simultaneously).');

        $this->callCommand('john_user', 'ROLE_TEAMLEAD', true);
    }

    public function testWithMissingUsername()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "username").');

        $this->callCommand(null, null, true);
    }
}
