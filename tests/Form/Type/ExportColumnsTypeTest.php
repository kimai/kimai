<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\ExportColumnsType;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Form\Type\ExportColumnsType
 */
class ExportColumnsTypeTest extends TypeTestCase
{
    public static function getTestData(): iterable
    {
        yield [['foo', 'bar'], []];

        yield [
            ['user.name', 'customer.meta.customer-foo', 'duration', 'hello', 'user.meta.mypref'],
            ['user.name', 'customer.meta.customer-foo', 'duration', 'user.meta.mypref']
        ];
    }

    /**
     * @return ExportColumnsType[]
     */
    protected function getTypes(): array
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $translator = $this->createMock(TranslatorInterface::class);

        return [
            new ExportColumnsType($dispatcher, $translator)
        ];
    }

    /**
     * @param array<mixed> $value
     * @param array<mixed> $expected
     * @dataProvider getTestData
     */
    public function testSubmitValidData(array $value, array $expected): void
    {
        $data = ['columns' => $value];
        $model = new TypeTestModel(['columns' => []]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('columns', ExportColumnsType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'columns' => $expected
        ]);

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }
}
