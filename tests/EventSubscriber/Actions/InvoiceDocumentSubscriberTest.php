<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\Entity\User;
use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\InvoiceDocumentSubscriber;
use App\Model\InvoiceDocument;

/**
 * @covers \App\EventSubscriber\Actions\InvoiceDocumentSubscriber
 */
class InvoiceDocumentSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(InvoiceDocumentSubscriber::class, 'invoice_document');
    }

    public function testActions(): void
    {
        $sut = $this->createSubscriber(InvoiceDocumentSubscriber::class, true);

        $event = new PageActionsEvent(new User(), ['document' => new InvoiceDocument(new \SplFileInfo(__FILE__)), 'token' => uniqid()], 'invoice_document', 'index');
        $sut->onActions($event);

        $actions = $event->getActions();
        self::assertGreaterThanOrEqual(1, \count($actions));
        self::assertArrayHasKey('trash', $actions);
        self::assertEquals('invoice_document_delete', $actions['trash']['url']);
    }
}
