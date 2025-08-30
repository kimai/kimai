<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Search;

use App\Entity\Timesheet;
use App\Repository\Query\BaseQuery;
use App\Repository\RepositoryException;
use App\Repository\Search\SearchConfiguration;
use App\Repository\Search\SearchHelper;
use App\Utils\SearchTerm;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchHelper::class)]
class SearchHelperTest extends TestCase
{
    public function testSearchTermIsNullDoesNotModifyQueryBuilder(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::never())->method('andWhere');

        $query = new BaseQuery();

        $configuration = new SearchConfiguration();
        $sut = new SearchHelper($configuration);

        $sut->addSearchTerm($qb, $query);
    }

    public function testNoAliasThrowsException(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getRootAliases')->willReturn([]);

        $query = new BaseQuery();
        $query->setSearchTerm(new SearchTerm('foo'));
        $configuration = new SearchConfiguration();
        $sut = new SearchHelper($configuration);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('No alias was set before invoking addSearchTerm().');

        $sut->addSearchTerm($qb, $query);
    }

    public function testSupportsMetaFieldsAddsMetaFieldConditions(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());
        $qb = new QueryBuilder($em);
        $qb->from(Timesheet::class, 'testFoo');

        $query = new BaseQuery();
        $query->setSearchTerm(new SearchTerm('metaField:value !foo test'));
        $configuration = new SearchConfiguration(['bar', 'tmp'], 'MetaFieldClass', 'metaFieldName');
        $configuration->setEntityFieldName('entityFieldName');

        $sut = new SearchHelper($configuration);

        $sut->addSearchTerm($qb, $query);
        $parts = $qb->getDQLParts();

        self::assertCount(9, $parts);
        self::assertArrayHasKey('join', $parts);
        self::assertIsArray($parts['join']);
        self::assertCount(1, $parts['join']);
        self::assertArrayHasKey('testFoo', $parts['join']);
        self::assertArrayHasKey('where', $parts);
        self::assertInstanceOf(Andx::class, $parts['where']);
        $whereParts = $parts['where'];
        self::assertEquals(1, $whereParts->count());

        $whereAnd = $whereParts->getParts()[0];
        self::assertInstanceOf(Andx::class, $whereAnd);
        self::assertCount(4, $whereAnd->getParts());

        // meta fields
        $where = $whereAnd->getParts()[0];
        self::assertInstanceOf(Andx::class, $where);
        $compareParts = $where->getParts();
        self::assertCount(2, $compareParts);

        self::assertInstanceOf(Comparison::class, $compareParts[0]);
        self::assertEquals('meta0.name', $compareParts[0]->getLeftExpr());
        self::assertEquals('=', $compareParts[0]->getOperator());
        self::assertEquals(':metaName0', $compareParts[0]->getRightExpr());

        self::assertInstanceOf(Comparison::class, $compareParts[1]);
        self::assertEquals('meta0.value', $compareParts[1]->getLeftExpr());
        self::assertEquals('LIKE', $compareParts[1]->getOperator());
        self::assertEquals(':metaValue0', $compareParts[1]->getRightExpr());

        // negated search terms
        $where = $whereAnd->getParts()[1];
        self::assertInstanceOf(Orx::class, $where);
        $compareParts = $where->getParts();
        self::assertCount(2, $compareParts);

        self::assertEquals('testFoo.bar IS NULL', $compareParts[0]);

        self::assertInstanceOf(Comparison::class, $compareParts[1]);
        self::assertEquals('testFoo.bar', $compareParts[1]->getLeftExpr());
        self::assertEquals('NOT LIKE', $compareParts[1]->getOperator());
        self::assertEquals(':searchTerm0', $compareParts[1]->getRightExpr());

        $where = $whereAnd->getParts()[2];
        self::assertInstanceOf(Orx::class, $where);
        $compareParts = $where->getParts();
        self::assertCount(2, $compareParts);

        self::assertEquals('testFoo.tmp IS NULL', $compareParts[0]);

        self::assertInstanceOf(Comparison::class, $compareParts[1]);
        self::assertEquals('testFoo.tmp', $compareParts[1]->getLeftExpr());
        self::assertEquals('NOT LIKE', $compareParts[1]->getOperator());
        self::assertEquals(':searchTerm1', $compareParts[1]->getRightExpr());

        // regular search terms
        $where = $whereAnd->getParts()[3];
        self::assertInstanceOf(Andx::class, $where);
        $compareParts = $where->getParts();
        self::assertCount(1, $compareParts);
        self::assertInstanceOf(Orx::class, $compareParts[0]);
        $orParts = $compareParts[0]->getParts();
        self::assertCount(2, $orParts);

        self::assertInstanceOf(Comparison::class, $orParts[0]);
        self::assertEquals('testFoo.bar', $orParts[0]->getLeftExpr());
        self::assertEquals('LIKE', $orParts[0]->getOperator());
        self::assertEquals(':searchTerm2', $orParts[0]->getRightExpr());

        self::assertInstanceOf(Comparison::class, $orParts[1]);
        self::assertEquals('testFoo.tmp', $orParts[1]->getLeftExpr());
        self::assertEquals('LIKE', $orParts[1]->getOperator());
        self::assertEquals(':searchTerm3', $orParts[1]->getRightExpr());

        /** @var array<Parameter> $parameters */
        $parameters = $qb->getParameters();
        self::assertCount(6, $parameters);

        self::assertInstanceOf(Parameter::class, $parameters[0]);
        self::assertEquals('metaValue0', $parameters[0]->getName());
        self::assertEquals('%value%', $parameters[0]->getValue());
        self::assertEquals(2, $parameters[0]->getType());

        self::assertInstanceOf(Parameter::class, $parameters[1]);
        self::assertEquals('metaName0', $parameters[1]->getName());
        self::assertEquals('metaField', $parameters[1]->getValue());
        self::assertEquals(2, $parameters[1]->getType());

        self::assertInstanceOf(Parameter::class, $parameters[2]);
        self::assertEquals('searchTerm0', $parameters[2]->getName());
        self::assertEquals('%foo%', $parameters[2]->getValue());
        self::assertEquals(2, $parameters[2]->getType());

        self::assertInstanceOf(Parameter::class, $parameters[3]);
        self::assertEquals('searchTerm1', $parameters[3]->getName());
        self::assertEquals('%foo%', $parameters[3]->getValue());
        self::assertEquals(2, $parameters[3]->getType());

        self::assertInstanceOf(Parameter::class, $parameters[4]);
        self::assertEquals('searchTerm2', $parameters[4]->getName());
        self::assertEquals('%test%', $parameters[4]->getValue());
        self::assertEquals(2, $parameters[4]->getType());

        self::assertInstanceOf(Parameter::class, $parameters[5]);
        self::assertEquals('searchTerm3', $parameters[5]->getName());
        self::assertEquals('%test%', $parameters[5]->getValue());
        self::assertEquals(2, $parameters[5]->getType());
    }

    public function testSearchTermWithExcludedFieldAddsExclusionCondition(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn(new Expr());
        $qb->method('getRootAliases')->willReturn(['root']);
        $qb->expects(self::atLeastOnce())->method('andWhere');

        $query = new BaseQuery();
        $query->setSearchTerm(new SearchTerm('!value'));
        $configuration = new SearchConfiguration(['field1', 'field2']);

        $sut = new SearchHelper($configuration);

        $sut->addSearchTerm($qb, $query);
    }
}
