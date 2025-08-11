<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\ExportRendererType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(ExportRendererType::class)]
class ExportRendererTypeTest extends TypeTestCase
{
    public static function getTestData(): iterable
    {
        yield ['foo', null];
        yield ['csv', 'csv'];
        yield ['csV', null];
        yield ['xlsx', 'xlsx'];
        yield ['XLSX', null];
    }

    #[DataProvider('getTestData')]
    public function testSubmitValidData(string $value, string|null $expected): void
    {
        $data = ['renderer' => $value];
        $model = new TypeTestModel(['renderer' => null]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('renderer', ExportRendererType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'renderer' => $expected
        ]);

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }
}
