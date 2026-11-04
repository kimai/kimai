<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Wizard;

use App\Entity\User;
use App\Event\WizardEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Central entry point for working with the user onboarding wizard.
 *
 * Dispatches the {@see WizardEvent} so every registered listener (Kimai
 * core and plugins) can contribute steps, then answers navigation questions
 * on top of the resulting ordered step list. This is the only place that
 * needs to know how the steps relate to each other — individual step
 * controllers never reference their neighbours by name.
 */
final class WizardManager
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @return WizardStep[] all registered steps for $user, sorted by order
     */
    public function getSteps(User $user): array
    {
        $event = new WizardEvent($user);
        // these stes
        $event->addStep(new WizardStep('intro', 'wizard_intro', 100));
        $event->addStep(new WizardStep('profile', 'wizard_profile', 200));

        $this->dispatcher->dispatch($event);

        return $event->getSteps();
    }

    /**
     * Returns the first step the user has not seen yet, or null if
     * everything is done.
     */
    public function getFirstUnseenStep(User $user): ?WizardStep
    {
        foreach ($this->getSteps($user) as $step) {
            if (!$user->hasSeenWizard($step->id)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Returns the step that comes after $currentStepId in the configured
     * order, or null if $currentStepId is unknown or is the last step.
     */
    public function getNextStep(User $user, string $currentStepId): ?WizardStep
    {
        $found = false;
        foreach ($this->getSteps($user) as $step) {
            if ($found) {
                return $step;
            }
            if ($step->id === $currentStepId) {
                $found = true;
            }
        }

        return null;
    }

    /**
     * Returns the step that comes before $currentStepId in the configured
     * order, or null if $currentStepId is unknown or is the first step.
     */
    public function getPreviousStep(User $user, string $currentStepId): ?WizardStep
    {
        $previous = null;
        foreach ($this->getSteps($user) as $step) {
            if ($step->id === $currentStepId) {
                return $previous;
            }
            $previous = $step;
        }

        return null;
    }

    /**
     * Resolve previous/next route names for a step via the WizardManager so
     * step controllers never reference their neighbours by name.
     *
     * @return array<string, mixed>
     */
    public function getNavigation(User $user, string $currentStepId): array
    {
        $previous = $this->getPreviousStep($user, $currentStepId);
        $next = $this->getNextStep($user, $currentStepId);

        return [
            'previous' => $previous?->route,
            'next' => $next?->route ?? 'wizard_finish', // @phpstan-ignore nullsafe.neverNull
        ];
    }
}
