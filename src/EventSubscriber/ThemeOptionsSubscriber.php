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
use KevinPapst\AdminLTEBundle\Helper\ContextHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Allows dynamic injection of theme related options.
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

        /** @var UserPreference $ref */
        foreach ($user->getPreferences() as $ref) {
            $name = $ref->getName();
            switch ($name) {
                case UserPreference::SKIN:
                    if (!empty($ref->getValue())) {
                        $this->helper->setOption('skin', 'skin-' . $ref->getValue());
                    }
                    break;

                case 'theme.layout':
                    if ($ref->getValue() === 'boxed') {
                        $this->helper->setOption('boxed_layout', true);
                        $this->helper->setOption('fixed_layout', false);
                    } elseif ($ref->getValue() === 'fixed') {
                        $this->helper->setOption('boxed_layout', false);
                        $this->helper->setOption('fixed_layout', true);
                    }
                    break;

                case 'theme.collapsed_sidebar':
                    $this->helper->setOption('collapsed_sidebar', $ref->getValue());
                    break;
            }
        }
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
        if (null === $this->storage->getToken()) {
            return false;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        return ($user instanceof User);
    }
}
