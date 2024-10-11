<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\InvoiceTemplateSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\InvoiceTemplateSubscriber
 */
class InvoiceTemplateSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(InvoiceTemplateSubscriber::class, 'invoice_template');
    }
}
