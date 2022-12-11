<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Authentication;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class ApiTokenMigratingListener implements EventSubscriberInterface
{
    public function __construct(private PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(ApiTokenUpgradeBadge::class)) {
            return;
        }

        /** @var ApiTokenUpgradeBadge $badge */
        $badge = $passport->getBadge(ApiTokenUpgradeBadge::class);
        $plaintextApiToken = $badge->getAndErasePlaintextApiToken();

        if ('' === $plaintextApiToken) {
            return;
        }

        $user = $passport->getUser();
        if (!($user instanceof User)) {
            return;
        }

        if (null === $user->getApiToken()) {
            return;
        }

        $passwordHasher = $this->hasherFactory->getPasswordHasher($user);
        if (!$passwordHasher->needsRehash($user->getApiToken())) {
            return;
        }

        $badge->getPasswordUpgrader()->upgradePassword($user, $passwordHasher->hash($plaintextApiToken));
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }
}
