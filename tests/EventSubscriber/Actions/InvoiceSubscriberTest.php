<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use App\EventSubscriber\Actions\InvoiceSubscriber;

#[CoversClass(InvoiceSubscriber::class)]
class InvoiceSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(InvoiceSubscriber::class, 'invoice');
    }
}
