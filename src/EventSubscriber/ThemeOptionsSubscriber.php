<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserPreference;
use Avanzu\AdminThemeBundle\Helper\ContextHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ThemeOptionsSubscriber implements EventSubscriberInterface
{

    /**
     * @var TokenStorageInterface
     */
    protected $storage;

    /**
     * @var ContextHelper
     */
    protected $helper;

    /**
     * ThemeOptionsSubscriber constructor.
     * @param TokenStorageInterface $storage
     * @param ContextHelper $helper
     */
    public function __construct(TokenStorageInterface $storage, ContextHelper $helper)
    {
        $this->storage = $storage;
        $this->helper = $helper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['setThemeOptions', 100]
        ];
    }

    /**
     * @param KernelEvent $event
     */
    public function setThemeOptions(KernelEvent $event)
    {
        if (!$this->canHandleEvent($event)) {
            return;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        $skin = '';
        foreach ($user->getPreferences() as $ref) {
            $name = $ref->getName();
            if ($name === UserPreference::SKIN) {
                $skin = 'skin-' . $ref->getValue();
            }

            if (strpos($name, 'theme.') !== false) {
                $this->helper->setOption(str_replace('theme.', '', $name), $ref->getValue());
            }
        }

        if (empty($skin)) {
            $skin = 'skin-green';
        }

        $this->helper->setOption('skin', $skin);
    }

    /**
     * @param KernelEvent $event
     * @return bool
     */
    protected function canHandleEvent(KernelEvent $event): bool
    {
        // Ignore sub-requests
        if (!$event->isMasterRequest()) {
            return false;
        }

        // ignore events like the toolbar where we do not have a token
        if ($this->storage->getToken() === null) {
            return false;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        return ($user instanceof User);
    }
}
