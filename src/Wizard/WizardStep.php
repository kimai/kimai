<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Wizard;

/**
 * A single step inside the user onboarding wizard.
 *
 * Plugins can register their own steps via the {@see \App\Event\WizardEvent}.
 * A step is identified by its id (used for the "seen" flag on {@see \App\Entity\User})
 * and points to an existing Symfony route that renders the step.
 *
 * The order property defines the position inside the wizard sequence — lower
 * numbers come first. Built-in steps use round numbers (100, 200, 300, ...),
 * leaving plenty of room for plugins to insert in between.
 */
final class WizardStep
{
    public function __construct(
        public readonly string $id,
        public readonly string $route,
        public readonly int $order = 0,
    ) {
    }
}
