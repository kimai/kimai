<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\UserLoginLinkCommand;
use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[CoversClass(UserLoginLinkCommand::class)]
#[Group('integration')]
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
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->application->add(new UserLoginLinkCommand($loginLinkHandler, $userRepository, $requestStack, $urlGenerator));
    }

    public function testCommandName(): void
    {
        $application = $this->application;

        $command = $application->find('kimai:user:login-link');
        self::assertInstanceOf(UserLoginLinkCommand::class, $command);
    }

    public function testAbsoluteOptionIsDisabledByDefault(): void
    {
        $command = $this->application->find('kimai:user:login-link');
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('absolute'));
        self::assertFalse($definition->getOption('absolute')->acceptValue());
        self::assertFalse($definition->getOption('absolute')->getDefault());
    }

    /**
     * @return iterable<string, array{0: bool}>
     */
    public static function getPasswordResetOptions(): iterable
    {
        yield 'login link' => [false];
        yield 'password reset' => [true];
    }

    /**
     * Regression test: requiring the password reset changes the security signature, which is part of the
     * login link hash. The link must be created afterwards, otherwise it is invalid immediately.
     */
    #[DataProvider('getPasswordResetOptions')]
    public function testCreatedLoginLinkIsValid(bool $passwordReset): void
    {
        $container = self::getContainer();
        $loginLinkHandler = $container->get(LoginLinkHandlerInterface::class);
        self::assertInstanceOf(LoginLinkHandlerInterface::class, $loginLinkHandler);
        $userRepository = $container->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $userRepository);
        $requestStack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $urlGenerator = $container->get(UrlGeneratorInterface::class);
        self::assertInstanceOf(UrlGeneratorInterface::class, $urlGenerator);

        $user = $userRepository->findByUsername(UserFixtures::USERNAME_USER);
        self::assertNotNull($user);
        self::assertFalse($user->requiresPasswordReset());

        $command = new UserLoginLinkCommand($loginLinkHandler, $userRepository, $requestStack, $urlGenerator);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['email' => $user->getEmail(), '--password-reset' => $passwordReset]);
        $commandTester->assertCommandIsSuccessful();
        $loginLink = trim($commandTester->getDisplay());

        // the password reset is required after the login link was used, see LoginLinkSuccessHandler
        self::assertFalse($user->requiresPasswordReset());
        self::assertSame($passwordReset, $user->isPasswordRequestNonExpired(3600));
        self::assertEquals('', $user->getSignatureDate());

        $request = Request::create($loginLink);
        $requestStack->push($request);
        try {
            $loggedInUser = $loginLinkHandler->consumeLoginLink($request);
        } finally {
            $requestStack->pop();
        }

        self::assertSame($user->getUserIdentifier(), $loggedInUser->getUserIdentifier());
    }
}
