<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\TimesheetMeta;
use App\Export\ExportPreviewColumn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportPreviewColumn::class)]
class ExportPreviewColumnTest extends TestCase
{
    public function testDescriptorValuesAndCollisionSafeTableKey(): void
    {
        $field = (new TimesheetMeta())->setName('bar_baz');
        $sut = new ExportPreviewColumn('foo_bar', 'bar_baz', 'Label', $field);

        self::assertSame('foo_bar', $sut->getRepositoryType());
        self::assertSame('bar_baz', $sut->getName());
        self::assertSame('Label', $sut->getLabel());
        self::assertSame($field, $sut->getField());
        self::assertSame('mf_666f6f5f626172_6261725f62617a', $sut->getTableKey());
    }
}
