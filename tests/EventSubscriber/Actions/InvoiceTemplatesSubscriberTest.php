<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\InvoiceTemplatesSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\InvoiceTemplatesSubscriber
 */
class InvoiceTemplatesSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(InvoiceTemplatesSubscriber::class, 'invoice_templates');
    }
}
