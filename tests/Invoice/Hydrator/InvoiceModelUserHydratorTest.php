<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceModelUserHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\Hydrator\InvoiceModelUserHydrator
 */
class InvoiceModelUserHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate(): void
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelUserHydrator();

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);
    }

    public function assertModelStructure(array $model): void
    {
        $keys = [
            'user.display',
            'user.alias',
            'user.email',
            'user.name',
            'user.title',
            'user.meta.hello',
            'user.meta.kitty',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        self::assertEquals($keys, $givenKeys);
    }
}
