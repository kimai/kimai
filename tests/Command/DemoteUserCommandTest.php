<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\DemoteUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\User\UserService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\AbstractRoleCommand
 * @covers \App\Command\DemoteUserCommand
 * @group integration
 */
class DemoteUserCommandTest extends KernelTestCase
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

        $this->application->add(new DemoteUserCommand($userService));
    }

    public function testCommandName()
    {
        $application = $this->application;

        $command = $application->find('kimai:user:demote');
        self::assertInstanceOf(DemoteUserCommand::class, $command);
    }

    protected function callCommand(?string $username, ?string $role, bool $super = false)
    {
        $command = $this->application->find('kimai:user:demote');
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

    public function testDemoteRole()
    {
        $commandTester = $this->callCommand('tony_teamlead', 'ROLE_TEAMLEAD');

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Role "ROLE_TEAMLEAD" has been removed from user "tony_teamlead".', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('tony_teamlead');
        self::assertInstanceOf(User::class, $user);
        self::assertFalse($user->hasTeamleadRole());
    }

    public function testDemoteSuper()
    {
        $commandTester = $this->callCommand('susan_super', null, true);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Super administrator role has been removed from the user "susan_super".', $output);

        $container = self::$kernel->getContainer();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get('doctrine')->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier('susan_super');
        self::assertInstanceOf(User::class, $user);
        self::assertFalse($user->isSuperAdmin());
    }

    public function testDemoteSuperFailsOnTeamlead()
    {
        $commandTester = $this->callCommand('tony_teamlead', null, true);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] User "tony_teamlead" doesn\'t have the super administrator role.', $output);
    }

    public function testDemoteAdminFailsOnTeamlead()
    {
        $commandTester = $this->callCommand('tony_teamlead', 'ROLE_ADMIN', false);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] User "tony_teamlead" didn\'t have "ROLE_ADMIN" role.', $output);
    }

    public function testDemoteRoleAndSuperFails()
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
