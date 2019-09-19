<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Paginator;

use App\Repository\Paginator\QueryBuilderPaginator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\Paginator\QueryBuilderPaginator
 */
class QueryBuilderPaginatorTest extends TestCase
{
    public function testPaginator()
    {
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $qb = new QueryBuilder($em);
        $sut = new QueryBuilderPaginator($qb, 10);

        self::assertEquals(10, $sut->getNbResults());
    }
}
