<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\WizardSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \App\EventSubscriber\WizardSubscriber
 */
class WizardSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WizardSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $methodName = $events[KernelEvents::REQUEST][0];
        $this->assertTrue(method_exists(WizardSubscriber::class, $methodName));
    }
}
