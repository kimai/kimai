<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\LocaleService;
use App\Entity\User;
use App\Entity\UserPreference;
use App\EventSubscriber\ThemeOptionsSubscriber;
use App\Tests\Mocks\SystemConfigurationFactory;
use KevinPapst\TablerBundle\Helper\ContextHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversClass(ThemeOptionsSubscriber::class)]
class ThemeOptionsSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([KernelEvents::CONTROLLER => ['setThemeOptions', 100]], ThemeOptionsSubscriber::getSubscribedEvents());
    }

    public function testUsesAuthenticationThemeWithoutAuthenticatedUser(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $helper = new ContextHelper();
        $sut = $this->createSut($storage, $helper, ['user' => ['theme' => 'dark']]);

        $sut->setThemeOptions($this->createMainRequestEvent('ar'));

        self::assertTrue($helper->isRightToLeft());
        self::assertTrue($helper->isDarkMode());
        self::assertFalse($helper->isThemeAuto());
        self::assertTrue($helper->isHeaderDark());
        self::assertTrue($helper->isNavbarDark());
        self::assertFalse($helper->isBoxedLayout());
        self::assertFalse($helper->isCondensedUserMenu());
        self::assertFalse($helper->isCondensedNavbar());
        self::assertFalse($helper->isNavbarOverlapping());
    }

    public function testUsesAuthenticationThemeForNonKimaiUserToken(): void
    {
        $securityUser = $this->createMock(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($securityUser);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $helper = new ContextHelper();
        $sut = $this->createSut($storage, $helper, ['user' => ['theme' => 'auto']]);

        $sut->setThemeOptions($this->createMainRequestEvent());

        self::assertFalse($helper->isDarkMode());
        self::assertTrue($helper->isThemeAuto());
        self::assertFalse($helper->isHeaderDark());
    }

    public function testUserThemeOverridesAuthenticationTheme(): void
    {
        $user = new User();
        $user->setPreferenceValue(UserPreference::SKIN, 'dark');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $helper = new ContextHelper();
        $sut = $this->createSut($storage, $helper, ['user' => ['theme' => 'auto']]);

        $sut->setThemeOptions($this->createMainRequestEvent());

        self::assertFalse($helper->isRightToLeft());
        self::assertTrue($helper->isDarkMode());
        self::assertFalse($helper->isThemeAuto());
        self::assertTrue($helper->isHeaderDark());
    }

    private function createSut(TokenStorageInterface $storage, ContextHelper $helper, array $settings = []): ThemeOptionsSubscriber
    {
        return new ThemeOptionsSubscriber(
            $storage,
            $helper,
            new LocaleService([
                'en' => LocaleService::DEFAULT_SETTINGS,
                'ar' => [
                    'date' => 'dd.MM.y',
                    'time' => 'H:mm',
                    'rtl' => true,
                    'translation' => false,
                ],
            ]),
            SystemConfigurationFactory::createStub($settings)
        );
    }

    private function createMainRequestEvent(string $locale = 'en'): KernelEvent
    {
        $request = new Request();
        $request->setLocale($locale);

        $event = $this->createMock(KernelEvent::class);
        $event->expects($this->once())->method('isMainRequest')->willReturn(true);
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        return $event;
    }
}
