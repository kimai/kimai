<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\UserLoginLinkCommand;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * @covers \App\Command\UserLoginLinkCommand
 * @group integration
 */
class UserLoginLinkCommandTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $loginLinkHandler = $this->createMock(LoginLinkHandlerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $requestStack = $this->createMock(RequestStack::class);

        $this->application->add(new UserLoginLinkCommand($loginLinkHandler, $userRepository, $requestStack));
    }

    public function testCommandName(): void
    {
        $application = $this->application;

        $command = $application->find('kimai:user:login-link');
        self::assertInstanceOf(UserLoginLinkCommand::class, $command);
    }
}
