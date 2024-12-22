<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceItemDefaultHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\Hydrator\InvoiceItemDefaultHydrator
 */
class InvoiceItemDefaultHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate(): void
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceItemDefaultHydrator();
        $sut->setInvoiceModel($model);

        $expected = [
            ['meta_fields' => ['entry.meta.foo-timesheet'], 'description' => '== jhg ljhg ', 'description_safe' => '== jhg ljhg '],
            ['meta_fields' => ['entry.meta.foo-timesheet', 'entry.meta.foo-timesheet2'], 'description' => '', 'description_safe' => 'activity description'],
            ['meta_fields' => ['entry.meta.foo-timesheet'], 'description' => '', 'description_safe' => 'activity description'],
            ['meta_fields' => ['entry.meta.foo-timesheet3']],
            ['meta_fields' => []],
        ];

        $i = 0;
        foreach ($model->getCalculator()->getEntries() as $entry) {
            $result = $sut->hydrate($entry);
            $exp = $expected[$i++];
            $this->assertEntryStructure($result, $exp['meta_fields']);
            if (\array_key_exists('description', $exp)) {
                self::assertEquals($exp['description'], $result['entry.description']);
            }
            if (\array_key_exists('description_safe', $exp)) {
                self::assertEquals($exp['description_safe'], $result['entry.description_safe']);
            }
        }
    }

    public function assertEntryStructure(array $model, array $metaFields): void
    {
        $keys = [
            'entry.row',
            'entry.description',
            'entry.description_safe',
            'entry.amount',
            'entry.rate',
            'entry.rate_nc',
            'entry.rate_plain',
            'entry.rate_internal',
            'entry.rate_internal_nc',
            'entry.rate_internal_plain',
            'entry.total',
            'entry.total_nc',
            'entry.total_plain',
            'entry.currency',
            'entry.duration',
            'entry.duration_format',
            'entry.duration_decimal',
            'entry.duration_minutes',
            'entry.begin',
            'entry.begin_time',
            'entry.begin_timestamp',
            'entry.end',
            'entry.end_time',
            'entry.end_timestamp',
            'entry.date',
            'entry.date_process',
            'entry.week',
            'entry.weekyear',
            'entry.user_id',
            'entry.user_name',
            'entry.user_alias',
            'entry.user_display',
            'entry.user_title',
            'entry.user_preference.foo',
            'entry.user_preference.mad',
            'entry.activity',
            'entry.activity_id',
            'entry.activity.meta.foo-activity',
            'entry.project',
            'entry.project_id',
            'entry.project.meta.foo-project',
            'entry.customer',
            'entry.customer_id',
            'entry.customer.meta.foo-customer',
            'entry.category',
            'entry.type',
            'entry.tags',
        ];

        if (\count($metaFields) > 0) {
            $keys = array_merge($keys, $metaFields);
        }

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $model);
        }

        $expectedKeys = array_merge([], $keys);
        sort($expectedKeys);
        $givenKeys = array_keys($model);
        sort($givenKeys);

        self::assertEquals($expectedKeys, $givenKeys);
        self::assertEquals(\count($keys), \count($givenKeys));
    }
}
