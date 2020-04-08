<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceModelActivityHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\Hydrator\InvoiceModelActivityHydrator
 */
class InvoiceModelActivityHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate()
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelActivityHydrator();

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);

        $model->getQuery()->setActivities([]);
        $result = $sut->hydrate($model);
        self::assertEmpty($result);
    }

    protected function assertModelStructure(array $model)
    {
        $keys = [
            'activity.id',
            'activity.name',
            'activity.comment',
            'activity.meta.foo-activity',
            'activity.1.id',
            'activity.1.name',
            'activity.1.comment',
            'activity.1.meta.foo-activity',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        $this->assertEquals($keys, $givenKeys);
    }
}
