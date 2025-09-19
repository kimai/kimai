<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Export\Template;
use App\Repository\Query\TimesheetQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Template::class)]
class TemplateTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $template = new Template('id', 'title');
        self::assertEquals('id', $template->getId());
        self::assertEquals('title', $template->getTitle());
        self::assertNull($template->getLocale());
        self::assertEquals([], $template->getColumns(new TimesheetQuery()));
        self::assertEquals([], $template->getOptions());
    }

    public function testSetsAndGetsColumnsCorrectly(): void
    {
        $template = new Template('id', 'title');
        $columns = ['Column1', 'Column2'];
        $template->setColumns($columns);
        self::assertEquals($columns, $template->getColumns(new TimesheetQuery()));
        $template->setColumns([]);
        self::assertEquals([], $template->getColumns(new TimesheetQuery()));
    }

    public function testSetsAndGetsOptionsCorrectly(): void
    {
        $template = new Template('id', 'title');
        $options = ['key1' => 'value1', 'key2' => 'value2'];
        $template->setOptions($options);
        self::assertEquals($options, $template->getOptions());
        $template->setOptions([]);
        self::assertEquals([], $template->getOptions());
    }

    public function testSetsAndGetsLocaleCorrectly(): void
    {
        $template = new Template('id', 'title');
        $template->setLocale('en_US');
        self::assertEquals('en_US', $template->getLocale());
    }

    public function testHandlesNullLocaleGracefully(): void
    {
        $template = new Template('id', 'title');
        $template->setLocale(null);
        self::assertNull($template->getLocale());
    }
}
