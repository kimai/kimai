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
use App\Form\Type\SkinType;
use KevinPapst\AdminLTEBundle\Helper\ContextHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Allows dynamic injection of theme related options.
 */
final class ThemeOptionsSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;
    /**
     * @var ContextHelper
     */
    private $helper;

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

    public function setThemeOptions(KernelEvent $event): void
    {
        // Ignore sub-requests
        if (!$event->isMasterRequest()) {
            return;
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
                    if (!empty($ref->getValue()) && \in_array($ref->getValue(), SkinType::THEMES)) {
                        $this->helper->setOption('skin', 'skin-' . $ref->getValue());
                    }
                    break;

                case 'theme.layout':
                    if ($ref->getValue() === 'boxed') {
                        $this->helper->setOption('boxed_layout', true);
                        $this->helper->setOption('fixed_layout', false);
                    } else {
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
}
