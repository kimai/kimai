<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Model\InvoiceModel;
use Symfony\Component\HttpFoundation\Response;

class DebugRendererTest extends AbstractRendererTest
{
    public function getTestModel()
    {
        yield [$this->getInvoiceModel(), '1,947.99', 5, 5, 1, 2, 2, true];
        yield [$this->getInvoiceModelOneEntry(), '293.27', 1, 1, 0, 1, 0, false];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender(InvoiceModel $model, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3, $hasProject)
    {
        $document = new InvoiceDocument(new \SplFileInfo(__DIR__ . '/DebugRenderer.php'));
        $sut = new DebugRenderer();
        /** @var Response $response */
        $response = $sut->render($document, $model);
        $data = json_decode($response->getContent(), true);

        $this->assertModelStructure($data['model'], $hasProject);
        $rows = $data['entries'];
        $this->assertEquals($expectedRows, count($rows));

        foreach ($rows as $row) {
            $this->assertEntryStructure($row);
        }

        // TODO check values or formats?
    }

    protected function assertModelStructure(array $model, $hasProject = true)
    {
        $keys = [
            'invoice.due_date',
            'invoice.date',
            'invoice.number',
            'invoice.currency',
            'invoice.vat',
            'invoice.tax',
            'invoice.total_time',
            'invoice.total',
            'invoice.subtotal',
            'template.name',
            'template.company',
            'template.address',
            'template.title',
            'template.payment_terms',
            'template.due_days',
            'query.begin',
            'query.end',
            'query.month',
            'query.year',
            'customer.id',
            'customer.address',
            'customer.name',
            'customer.contact',
            'customer.company',
            'customer.country',
            'customer.number',
            'customer.homepage',
            'customer.comment',
        ];

        if ($hasProject) {
            $keys = array_merge($keys, [
                'project.id',
                'project.name',
                'project.comment',
                'project.order_number',
            ]);
        }

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        $this->assertEquals($keys, $givenKeys);
    }

    protected function assertEntryStructure(array $model)
    {
        $keys = [
            'entry.description',
            'entry.amount',
            'entry.rate',
            'entry.total',
            'entry.duration',
            'entry.duration_minutes',
            'entry.begin',
            'entry.begin_time',
            'entry.begin_timestamp',
            'entry.end',
            'entry.end_time',
            'entry.end_timestamp',
            'entry.date',
            'entry.user_id',
            'entry.user_name',
            'entry.user_alias',
            'entry.user_title',
            'entry.activity',
            'entry.activity_id',
            'entry.project',
            'entry.customer',
            'entry.project_id',
            'entry.customer_id',
        ];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $model);
        }

        $expectedKeys = array_merge([], $keys);
        sort($expectedKeys);
        $givenKeys = array_keys($model);
        sort($givenKeys);

        $this->assertEquals(count($keys), count($givenKeys));
        $this->assertEquals($expectedKeys, $givenKeys);
    }
}
