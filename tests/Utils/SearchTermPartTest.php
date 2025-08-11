<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\SearchTermPart;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchTermPart::class)]
class SearchTermPartTest extends TestCase
{
    public static function getTestData(): array
    {
        return [
            ['foo bar test 1', 'foo bar test 1', null, false],
            ['foo:bar test 1', 'bar test 1', 'foo', false],
            ['foo:!bar test 1', 'bar test 1', 'foo', true],
            ['!bar', 'bar', null, true],
            ['bar', 'bar', null, false],
            ['hello:world', 'world', 'hello', false],
            ['hello:!world', 'world', 'hello', true],
            ['url:https://www.example.com', 'https://www.example.com', 'url', false],
            ['url:!https://www.example.com:8080/test.html', 'https://www.example.com:8080/test.html', 'url', true],
        ];
    }

    #[DataProvider('getTestData')]
    public function testSearchTerm(string $term, string $expected, ?string $field, bool $excluded): void
    {
        $sut = new SearchTermPart($term);
        self::assertEquals($expected, $sut->getTerm());
        self::assertEquals($field, $sut->getField());
        self::assertEquals($excluded, $sut->isExcluded());
    }
}
