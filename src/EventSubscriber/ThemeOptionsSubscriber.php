<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\LocaleService;
use App\Entity\User;
use App\Entity\UserPreference;
use KevinPapst\TablerBundle\Helper\ContextHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Prepare all theme settings for user and context.
 */
final class ThemeOptionsSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $storage, private ContextHelper $helper, private LocaleService $localeService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['setThemeOptions', 100]
        ];
    }

    public function setThemeOptions(KernelEvent $event): void
    {
        // Ignore sub-requests
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->localeService->isRightToLeft(\Locale::getDefault())) {
            $this->helper->setIsRightToLeft(true);
        }

        // ignore events like the toolbar where we do not have a token
        if (null === $this->storage->getToken()) {
            return;
        }

        $user = $this->storage->getToken()->getUser();

        if (!($user instanceof User)) {
            return;
        }

        if ($user->isSmallLayout()) {
            $this->helper->setIsBoxedLayout(true);
            $this->helper->setIsCondensedUserMenu(true);
        }

        $skin = $user->getPreferenceValue(UserPreference::SKIN);
        if ($skin === 'dark') {
            $this->helper->setIsDarkMode(true);
        }
        /*
                $sidebar = $user->getPreferenceValue('collapsed_sidebar');
                if ($sidebar !== null) {
                    $this->helper->setIsCondensedNavbar($ref->getValue());
                }
        */
        $this->helper->setIsNavbarOverlapping(!$this->helper->isDarkMode());
    }
}
