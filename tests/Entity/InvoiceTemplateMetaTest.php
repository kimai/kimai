<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\EntityWithMetaFields;
use App\Entity\InvoiceTemplate;
use App\Entity\InvoiceTemplateMeta;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Timesheet;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InvoiceTemplateMeta::class)]
class InvoiceTemplateMetaTest extends AbstractMetaEntityTestCase
{
    protected function getEntity(): EntityWithMetaFields
    {
        return new InvoiceTemplate();
    }

    protected function getMetaEntity(): MetaTableTypeInterface
    {
        return new InvoiceTemplateMeta();
    }

    public function testSetEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instanceof InvoiceTemplate, received "App\Entity\Timesheet"');

        $sut = new InvoiceTemplateMeta();
        $sut->setEntity(new Timesheet());
    }
}
