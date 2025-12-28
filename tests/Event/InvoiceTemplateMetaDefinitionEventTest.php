<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\InvoiceTemplate;
use App\Event\InvoiceTemplateMetaDefinitionEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceTemplateMetaDefinitionEvent::class)]
class InvoiceTemplateMetaDefinitionEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $invoiceTemplate = new InvoiceTemplate();
        $sut = new InvoiceTemplateMetaDefinitionEvent($invoiceTemplate);
        self::assertSame($invoiceTemplate, $sut->getEntity());
    }
}
