<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\WizardSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

#[CoversClass(WizardSubscriber::class)]
class WizardSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WizardSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(KernelEvents::REQUEST, $events);
        $methodName = $events[KernelEvents::REQUEST][0];
        self::assertIsString($methodName);
        self::assertTrue(method_exists(WizardSubscriber::class, $methodName));
    }
}
