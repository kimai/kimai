<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

abstract class AbstractLoaderTest extends TestCase
{
    protected function getEntityManagerMock(int $createQueryBuilderCount)
    {
        $em = $this->createMock(EntityManager::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $expr = $this->createMock(Expr::class);

        $expr->expects($this->any())->method('isNotNull')->willReturn('');
        $expr->expects($this->any())->method('in')->willReturn('');

        $qb->expects($this->any())->method('andWhere')->willReturnSelf();
        $qb->expects($this->any())->method('from')->willReturnSelf();
        $qb->expects($this->any())->method('expr')->willReturn($expr);
        $qb->expects($this->any())->method('from')->willReturnSelf();
        $qb->expects($this->any())->method('select')->willReturnSelf();
        $qb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $qb->expects($this->any())->method('getQuery')->willReturn($query);
        $query->expects($this->any())->method('execute')->willReturn(null);

        $em->expects($this->exactly($createQueryBuilderCount))->method('createQueryBuilder')->willReturn($qb);

        return $em;
    }
}
