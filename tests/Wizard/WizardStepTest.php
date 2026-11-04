<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Wizard;

use App\Wizard\WizardStep;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WizardStep::class)]
class WizardStepTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $step = new WizardStep('intro', 'wizard_intro', 100);

        self::assertSame('intro', $step->id);
        self::assertSame('wizard_intro', $step->route);
        self::assertSame(100, $step->order);
    }

    public function testOrderDefaultsToZero(): void
    {
        $step = new WizardStep('intro', 'wizard_intro');

        self::assertSame(0, $step->order);
    }
}
