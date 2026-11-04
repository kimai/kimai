<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\Wizard\WizardStep;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched once per request whenever Kimai needs to know which wizard
 * steps exist for a given user. Listeners (Kimai core and plugins) add
 * their steps via {@see self::addStep()} and may inspect the user to
 * decide whether their step applies (e.g. only when a password reset
 * is required).
 *
 * Steps do not need to know about each other — order and navigation are
 * resolved by {@see \App\Wizard\WizardManager} based on the {@see WizardStep::$order}
 * value.
 */
final class WizardEvent extends Event
{
    /**
     * @var array<string, WizardStep>
     */
    private array $steps = [];

    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Register a wizard step. If a step with the same id was already
     * registered, it is replaced — listeners with a higher priority win.
     */
    public function addStep(WizardStep $step): void
    {
        $this->steps[$step->id] = $step;
    }

    public function hasStep(string $id): bool
    {
        return \array_key_exists($id, $this->steps);
    }

    public function getStep(string $id): ?WizardStep
    {
        return $this->steps[$id] ?? null;
    }

    public function removeStep(string $id): void
    {
        if (\array_key_exists($id, $this->steps)) {
            unset($this->steps[$id]);
        }
    }

    /**
     * @return WizardStep[] steps sorted by their order (ascending)
     */
    public function getSteps(): array
    {
        $steps = array_values($this->steps);
        usort($steps, static fn (WizardStep $a, WizardStep $b): int => $a->order <=> $b->order);

        return $steps;
    }

    /**
     * Convenience wrapper around {@see self::addStep()} that creates a
     * {@see WizardStep} from an id and route, appending it to the end of
     * the current step list.
     */
    public function addWizard(string $wizard, string $route): void
    {
        $this->addStep(new WizardStep($wizard, $route, (\count($this->steps) + 1) * 100));
    }

    /**
     * @return array<string, string> map of step id => route name, in order
     */
    public function getWizards(): array
    {
        $result = [];
        foreach ($this->getSteps() as $step) {
            $result[$step->id] = $step->route;
        }

        return $result;
    }

    /**
     * Returns the route name of the step that follows $wizard in the
     * configured order, or the fallback route 'wizard_finish' if $wizard
     * is unknown or is the last step.
     */
    public function getNextWizard(string $wizard): string
    {
        $steps = $this->getSteps();
        $found = false;
        foreach ($steps as $step) {
            if ($found) {
                return $step->route;
            }
            if ($step->id === $wizard) {
                $found = true;
            }
        }

        return 'wizard_finish';
    }
}
