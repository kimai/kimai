<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Repository\Query\TimesheetQuery;
use App\Utils\Pagination;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\Pagination
 */
class PaginationTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new Pagination(new ArrayAdapter([]));
        self::assertEquals(1, $sut->getCurrentPage());
        self::assertEquals(10, $sut->getMaxPerPage());
        self::assertTrue($sut->getNormalizeOutOfRangePages());
    }

    public function testDefaultQuery(): void
    {
        $query = new TimesheetQuery();
        $sut = new Pagination(new ArrayAdapter([]), $query);
        self::assertEquals(1, $sut->getCurrentPage());
        self::assertEquals(50, $sut->getMaxPerPage());
        self::assertTrue($sut->getNormalizeOutOfRangePages());
    }

    public function testWithParams(): void
    {
        $query = new TimesheetQuery();
        $query->setPage(3);
        $query->setPageSize(1);
        $query->setIsApiCall(true);
        $sut = new Pagination(new ArrayAdapter([1, 2, 3, 4, 5]), $query);
        self::assertFalse($sut->getNormalizeOutOfRangePages());
        self::assertEquals(1, $sut->getMaxPerPage());
        self::assertEquals(3, $sut->getCurrentPage());
    }
}
