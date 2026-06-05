<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Wizard\WizardManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Responsible for displaying the correct wizard
 */
class WizardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $storage,
        private readonly SystemConfiguration $systemConfiguration,
        private readonly WizardManager $wizardManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -30]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // ignore sub-requests and un-authenticated events
        if (!$event->isMainRequest() || null === ($token = $this->storage->getToken())) {
            return;
        }

        $uri = $event->getRequest()->getRequestUri();

        // TODO 3.0 remove /register/
        if (stripos($uri, '/register/') !== false) {
            return;
        }

        // never trigger wizard on API calls
        if (str_starts_with($uri, '/api/')) {
            return;
        }

        // never trigger on wizard routes themselves — except the virtual /next/
        // route, which intentionally re-enters this subscriber so that the user
        // is redirected to the first step they have not seen yet.
        if (stripos($uri, '/wizard/') !== false && stripos($uri, '/wizard/next/') === false) {
            return;
        }

        $user = $token->getUser();

        if (!($user instanceof User)) {
            return;
        }

        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        if ($user->isRegularUserOnly() && !$this->systemConfiguration->isUserWizardActive()) {
            return;
        }

        $step = $this->wizardManager->getFirstUnseenStep($user);

        if ($step === null) {
            // All registered steps have been seen — fall through to the regular
            // application response. If we were intercepting /wizard/next/, the
            // controller will redirect to wizard_finish.
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate($step->route)));
    }
}
