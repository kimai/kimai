<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Event\WizardEvent;
use App\EventSubscriber\WizardSubscriber;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Wizard\WizardManager;
use App\Wizard\WizardStep;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversClass(WizardSubscriber::class)]
class WizardSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([KernelEvents::REQUEST => ['onKernelRequest', -30]], WizardSubscriber::getSubscribedEvents());
    }

    public function testOnKernelRequestIgnoresSubRequest(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->never())->method('getToken');

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub(), $this->createWizardManager());
        $event = $this->createRequestEvent('/dashboard', false);

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresMissingToken(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->never())->method('isGranted');

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub(), $this->createWizardManager());
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideExcludedUris(): iterable
    {
        yield ['/api/timesheets'];
        yield ['/register/new'];
        yield ['/wizard/intro'];
    }

    #[DataProvider('provideExcludedUris')]
    public function testOnKernelRequestIgnoresExcludedUris(string $uri): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->never())->method('getUser');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->never())->method('isGranted');

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub(), $this->createWizardManager());
        $event = $this->createRequestEvent($uri);

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresNonUserToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($this->createMock(UserInterface::class));

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->never())->method('isGranted');

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub([
            'user' => [
                'wizard' => true,
            ]
        ]), $this->createWizardManager());
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresUserWithoutFullAuthentication(): void
    {
        $user = new User();
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(false);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub([
            'user' => [
                'wizard' => true,
            ]
        ]), $this->createWizardManager());
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresWizardForRegularUserIfDisabled(): void
    {
        $user = new User();
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->never())->method('generate');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub([
            'user' => [
                'wizard' => false,
            ]
        ]), $this->createWizardManager());
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestRedirectsToFirstUnseenWizard(): void
    {
        $user = new User();
        $user->setWizardAsSeen('intro');
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('wizard_profile')
            ->willReturn('/wizard/profile');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $manager = $this->createWizardManager(static function (WizardEvent $event): void {
            $event->addStep(new WizardStep('intro', 'wizard_intro', 100));
            $event->addStep(new WizardStep('profile', 'wizard_profile', 200));
        });

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub([
            'user' => [
                'wizard' => true,
            ]
        ]), $manager);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/wizard/profile', $response->headers->get('Location'));
    }

    public function testOnKernelRequestDoesNotRedirectWhenAllStepsSeen(): void
    {
        $user = new User();
        $user->setWizardAsSeen('intro');
        $user->setWizardAsSeen('profile');
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->never())->method('generate');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $manager = $this->createWizardManager(static function (WizardEvent $event): void {
            $event->addStep(new WizardStep('intro', 'wizard_intro', 100));
            $event->addStep(new WizardStep('profile', 'wizard_profile', 200));
        });

        $sut = new WizardSubscriber($urlGenerator, $security, $storage, SystemConfigurationFactory::createStub([
            'user' => [
                'wizard' => true,
            ]
        ]), $manager);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    private function createUserToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        return $token;
    }

    private function createRequestEvent(string $uri, bool $mainRequest = true): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($uri);

        return new RequestEvent($kernel, $request, $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @param (callable(WizardEvent): void)|null $stepRegistrar
     */
    private function createWizardManager(?callable $stepRegistrar = null): WizardManager
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event) use ($stepRegistrar): object {
            if ($stepRegistrar !== null && $event instanceof WizardEvent) {
                $stepRegistrar($event);
            }

            return $event;
        });

        return new WizardManager($dispatcher);
    }
}
