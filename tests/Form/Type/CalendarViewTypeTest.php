<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\CalendarViewType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(CalendarViewType::class)]
class CalendarViewTypeTest extends TypeTestCase
{
    /**
     * @return iterable<int, array<int, string>>
     */
    public static function getTestData(): iterable
    {
        yield ['month', 'month'];
        yield ['week', 'week'];
        yield ['day', 'day'];
    }

    #[DataProvider('getTestData')]
    public function testSubmitValidData(string $value, string $expected): void
    {
        $data = ['view' => $value];
        $model = new TypeTestModel(['view' => 'some']);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('view', CalendarViewType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'view' => $expected
        ]);

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }
}
