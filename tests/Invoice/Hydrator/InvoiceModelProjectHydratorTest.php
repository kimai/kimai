<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceModelProjectHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\Hydrator\InvoiceModelProjectHydrator
 */
class InvoiceModelProjectHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate()
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelProjectHydrator();

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);

        $model->getQuery()->setProjects([]);
        $result = $sut->hydrate($model);
        self::assertEmpty($result);
    }

    protected function assertModelStructure(array $model)
    {
        $keys = [
            'project.id',
            'project.name',
            'project.comment',
            'project.order_date',
            'project.order_number',
            'project.meta.foo-project',
            'project.start_date',
            'project.end_date',
            'project.budget_money',
            'project.budget_money_nc',
            'project.budget_money_plain',
            'project.budget_time',
            'project.budget_time_decimal',
            'project.budget_time_minutes',
            'project.1.id',
            'project.1.name',
            'project.1.comment',
            'project.1.order_date',
            'project.1.order_number',
            'project.1.meta.foo-project',
            'project.1.start_date',
            'project.1.end_date',
            'project.1.budget_money',
            'project.1.budget_money_nc',
            'project.1.budget_money_plain',
            'project.1.budget_time',
            'project.1.budget_time_decimal',
            'project.1.budget_time_minutes',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        $this->assertEquals($keys, $givenKeys);
    }
}
