<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WizardEvent;
use App\Wizard\WizardStep;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WizardEvent::class)]
class WizardEventTest extends TestCase
{
    public function testGetUserReturnsConstructorArgument(): void
    {
        $user = new User();
        $sut = new WizardEvent($user);

        self::assertSame($user, $sut->getUser());
    }

    public function testInitiallyEmpty(): void
    {
        $sut = new WizardEvent(new User());

        self::assertSame([], $sut->getSteps());
        self::assertSame([], $sut->getWizards());
        self::assertFalse($sut->hasStep('intro'));
        self::assertNull($sut->getStep('intro'));
    }

    public function testAddStepAndAccessors(): void
    {
        $sut = new WizardEvent(new User());
        $step = new WizardStep('intro', 'wizard_intro', 100);

        $sut->addStep($step);

        self::assertTrue($sut->hasStep('intro'));
        self::assertSame($step, $sut->getStep('intro'));
        self::assertSame([$step], $sut->getSteps());
    }

    public function testAddStepReplacesStepWithSameId(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));
        $replacement = new WizardStep('intro', 'wizard_intro_v2', 100);

        $sut->addStep($replacement);

        self::assertSame($replacement, $sut->getStep('intro'));
        self::assertCount(1, $sut->getSteps());
    }

    public function testGetStepsIsSortedByOrderAscending(): void
    {
        $sut = new WizardEvent(new User());
        $intro = new WizardStep('intro', 'wizard_intro', 100);
        $profile = new WizardStep('profile', 'wizard_profile', 200);
        $plugin = new WizardStep('plugin', 'plugin_step', 150);

        // intentionally added out of order
        $sut->addStep($profile);
        $sut->addStep($intro);
        $sut->addStep($plugin);

        self::assertSame([$intro, $plugin, $profile], $sut->getSteps());
    }

    public function testRemoveStep(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));
        $sut->addStep(new WizardStep('profile', 'wizard_profile', 200));

        $sut->removeStep('intro');

        self::assertFalse($sut->hasStep('intro'));
        self::assertTrue($sut->hasStep('profile'));
        self::assertCount(1, $sut->getSteps());
    }

    public function testRemoveUnknownStepIsNoop(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));

        $sut->removeStep('does-not-exist');

        self::assertCount(1, $sut->getSteps());
    }

    public function testAddWizardCreatesStepsInInsertionOrder(): void
    {
        $sut = new WizardEvent(new User());

        $sut->addWizard('intro', 'wizard_intro');
        $sut->addWizard('profile', 'wizard_profile');

        $steps = $sut->getSteps();
        self::assertCount(2, $steps);
        self::assertSame('intro', $steps[0]->id);
        self::assertSame('wizard_intro', $steps[0]->route);
        self::assertSame('profile', $steps[1]->id);
        self::assertSame('wizard_profile', $steps[1]->route);
        // addWizard must assign ascending order values so insertion order is preserved
        self::assertLessThan($steps[1]->order, $steps[0]->order);
    }

    public function testGetWizardsReturnsIdToRouteMapInOrder(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('profile', 'wizard_profile', 200));
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));

        self::assertSame(
            ['intro' => 'wizard_intro', 'profile' => 'wizard_profile'],
            $sut->getWizards()
        );
    }

    public function testGetNextWizardReturnsRouteOfFollowingStep(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));
        $sut->addStep(new WizardStep('profile', 'wizard_profile', 200));

        self::assertSame('wizard_profile', $sut->getNextWizard('intro'));
    }

    public function testGetNextWizardReturnsFinishForLastStep(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));
        $sut->addStep(new WizardStep('profile', 'wizard_profile', 200));

        self::assertSame('wizard_finish', $sut->getNextWizard('profile'));
    }

    public function testGetNextWizardReturnsFinishForUnknownStep(): void
    {
        $sut = new WizardEvent(new User());
        $sut->addStep(new WizardStep('intro', 'wizard_intro', 100));

        self::assertSame('wizard_finish', $sut->getNextWizard('does-not-exist'));
    }

    public function testGetNextWizardOnEmptyEventReturnsFinish(): void
    {
        $sut = new WizardEvent(new User());

        self::assertSame('wizard_finish', $sut->getNextWizard('anything'));
    }
}
