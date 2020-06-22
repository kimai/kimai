<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\SearchTermTransformer;
use App\Utils\SearchTerm;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\DataTransformer\SearchTermTransformer
 */
class SearchTermTransformerTest extends TestCase
{
    public function testTransform()
    {
        $sut = new SearchTermTransformer();

        self::assertEquals('', $sut->transform(''));
        self::assertEquals('', $sut->transform(null));
        self::assertEquals('', $sut->transform(new \stdClass()));

        self::assertEquals(
            'hello world:xxxxx foo bar test:1234',
            $sut->transform(new SearchTerm('hello world:xxxxx foo bar test:1234'))
        );
    }

    public function testReverseTransform()
    {
        $sut = new SearchTermTransformer();

        self::assertNull($sut->reverseTransform(''));
        self::assertNull($sut->reverseTransform(null));

        $term = $sut->reverseTransform('hello world:xxxxx foo bar test:1234');

        self::assertInstanceOf(SearchTerm::class, $term);
        self::assertEquals('hello world:xxxxx foo bar test:1234', $term->getOriginalSearch());
        self::assertEquals('hello foo bar', $term->getSearchTerm());
        self::assertEquals(['world' => 'xxxxx', 'test' => '1234'], $term->getSearchFields());
        self::assertEquals('xxxxx', $term->getSearchField('world'));
        self::assertEquals('1234', $term->getSearchField('test'));
    }
}
