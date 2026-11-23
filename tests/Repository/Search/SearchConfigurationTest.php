<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Search;

use App\Repository\Search\SearchConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchConfiguration::class)]
class SearchConfigurationTest extends TestCase
{
    public function testDefaultConstruct(): void
    {
        $sut = new SearchConfiguration();
        self::assertNull($sut->getMetaFieldClass());
        self::assertNull($sut->getMetaFieldName());
        self::assertEquals('meta', $sut->getEntityFieldName());
        self::assertEquals([], $sut->getSearchableFields());
    }

    public function testConstruct(): void
    {
        $fields = ['field1', 'field2'];
        $sut = new SearchConfiguration($fields, 'SomeClassName', 'foo-bar');
        self::assertEquals($fields, $sut->getSearchableFields());
        self::assertEquals('SomeClassName', $sut->getMetaFieldClass());
        self::assertEquals('foo-bar', $sut->getMetaFieldName());

        $sut->setEntityFieldName('customField');
        self::assertEquals('customField', $sut->getEntityFieldName());
    }
}
