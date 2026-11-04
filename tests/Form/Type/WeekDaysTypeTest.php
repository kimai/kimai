<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\WeekDaysType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(WeekDaysType::class)]
class WeekDaysTypeTest extends TypeTestCase
{
    public function testSubmitNull(): void
    {
        $data = ['week_days' => null];
        $model = new TypeTestModel(['week_days' => null]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('week_days', WeekDaysType::class);
        $form = $form->getForm();
        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals('', $model->offsetGet('week_days'));
    }

    public function testSubmitValidData(): void
    {
        $data = ['week_days' => ['tuesday', 'friday', 'sunday']];
        $model = new TypeTestModel(['week_days' => null]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('week_days', WeekDaysType::class);
        $form = $form->getForm();

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals('tuesday,friday,sunday', $model->offsetGet('week_days'));
    }
}
