<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Utils\LocaleSettings;
use KevinPapst\TablerBundle\Helper\ContextHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Allows dynamic injection of theme related options.
 */
final class ThemeOptionsSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $storage, private ContextHelper $helper, private LocaleSettings $localeSettings)
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

        if ($this->localeSettings->isRightToLeft()) {
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

        /** @var UserPreference $ref */
        foreach ($user->getPreferences() as $ref) {
            $name = $ref->getName();
            switch ($name) {
                case UserPreference::SKIN:
                    if ($ref->getValue() === 'dark') {
                        $this->helper->setIsDarkMode(true);
                    }
                    break;

                case 'theme.layout':
                    $value = ($ref->getValue() === 'boxed');
                    $this->helper->setIsBoxedLayout($value);
                    $this->helper->setIsCondensedUserMenu($value);
                    break;

                case 'theme.collapsed_sidebar':
                    //$this->helper->setIsCondensedNavbar($ref->getValue());
                    break;
            }
        }

        $this->helper->setIsNavbarOverlapping(!$this->helper->isDarkMode());
    }
}
