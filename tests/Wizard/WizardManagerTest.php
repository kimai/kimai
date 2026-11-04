<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Wizard;

use App\Entity\User;
use App\Event\WizardEvent;
use App\Wizard\WizardManager;
use App\Wizard\WizardStep;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

#[CoversClass(WizardManager::class)]
class WizardManagerTest extends TestCase
{
    public function testGetStepsIncludesBuiltInIntroAndProfile(): void
    {
        $sut = $this->createManager();

        $steps = $sut->getSteps(new User());

        self::assertCount(2, $steps);
        self::assertSame('intro', $steps[0]->id);
        self::assertSame('wizard_intro', $steps[0]->route);
        self::assertSame('profile', $steps[1]->id);
        self::assertSame('wizard_profile', $steps[1]->route);
    }

    public function testGetStepsIncludesPluginStepsInSortedOrder(): void
    {
        $sut = $this->createManager(static function (WizardEvent $event): void {
            // plugin step inserted between intro (100) and profile (200)
            $event->addStep(new WizardStep('plugin', 'plugin_route', 150));
        });

        $ids = array_map(static fn (WizardStep $s): string => $s->id, $sut->getSteps(new User()));

        self::assertSame(['intro', 'plugin', 'profile'], $ids);
    }

    public function testGetStepsDispatchesEventOncePerCall(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnArgument(0);

        $sut = new WizardManager($dispatcher);
        $sut->getSteps(new User());
        $sut->getSteps(new User());
    }

    public function testGetFirstUnseenStepReturnsFirstStepInitially(): void
    {
        $sut = $this->createManager();

        $step = $sut->getFirstUnseenStep(new User());

        self::assertNotNull($step);
        self::assertSame('intro', $step->id);
    }

    public function testGetFirstUnseenStepSkipsSeenSteps(): void
    {
        $sut = $this->createManager();
        $user = new User();
        $user->setWizardAsSeen('intro');

        $step = $sut->getFirstUnseenStep($user);

        self::assertNotNull($step);
        self::assertSame('profile', $step->id);
    }

    public function testGetFirstUnseenStepReturnsNullWhenAllSeen(): void
    {
        $sut = $this->createManager();
        $user = new User();
        $user->setWizardAsSeen('intro');
        $user->setWizardAsSeen('profile');

        self::assertNull($sut->getFirstUnseenStep($user));
    }

    public function testGetNextStepReturnsFollowingStep(): void
    {
        $sut = $this->createManager();

        $step = $sut->getNextStep(new User(), 'intro');

        self::assertNotNull($step);
        self::assertSame('profile', $step->id);
    }

    public function testGetNextStepReturnsNullForLastStep(): void
    {
        $sut = $this->createManager();

        self::assertNull($sut->getNextStep(new User(), 'profile'));
    }

    public function testGetNextStepReturnsNullForUnknownStep(): void
    {
        $sut = $this->createManager();

        self::assertNull($sut->getNextStep(new User(), 'does-not-exist'));
    }

    public function testGetPreviousStepReturnsPrecedingStep(): void
    {
        $sut = $this->createManager();

        $step = $sut->getPreviousStep(new User(), 'profile');

        self::assertNotNull($step);
        self::assertSame('intro', $step->id);
    }

    public function testGetPreviousStepReturnsNullForFirstStep(): void
    {
        $sut = $this->createManager();

        self::assertNull($sut->getPreviousStep(new User(), 'intro'));
    }

    public function testGetPreviousStepReturnsNullForUnknownStep(): void
    {
        $sut = $this->createManager();

        self::assertNull($sut->getPreviousStep(new User(), 'does-not-exist'));
    }

    public function testGetNavigationReturnsPreviousAndNextRouteNames(): void
    {
        $sut = $this->createManager();

        self::assertSame(
            ['previous' => 'wizard_intro', 'next' => 'wizard_finish'],
            $sut->getNavigation(new User(), 'profile')
        );
    }

    public function testGetNavigationFallsBackToWizardFinishForLastStep(): void
    {
        $sut = $this->createManager();

        $nav = $sut->getNavigation(new User(), 'profile');

        self::assertSame('wizard_finish', $nav['next']);
    }

    public function testGetNavigationReturnsNullPreviousForFirstStep(): void
    {
        $sut = $this->createManager();

        $nav = $sut->getNavigation(new User(), 'intro');

        self::assertNull($nav['previous']);
        self::assertSame('wizard_profile', $nav['next']);
    }

    public function testGetNavigationResolvesPluginNeighbours(): void
    {
        $sut = $this->createManager(static function (WizardEvent $event): void {
            // inserts itself between intro (100) and profile (200)
            $event->addStep(new WizardStep('plugin', 'plugin_route', 150));
        });

        self::assertSame(
            ['previous' => 'wizard_intro', 'next' => 'wizard_profile'],
            $sut->getNavigation(new User(), 'plugin')
        );
    }

    /**
     * @param (callable(WizardEvent): void)|null $stepRegistrar
     */
    private function createManager(?callable $stepRegistrar = null): WizardManager
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event) use ($stepRegistrar): object {
            if ($stepRegistrar !== null && $event instanceof WizardEvent) {
                $stepRegistrar($event);
            }

            return $event;
        });

        return new WizardManager($dispatcher);
    }
}
