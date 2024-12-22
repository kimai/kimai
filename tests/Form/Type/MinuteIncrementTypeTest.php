<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\MinuteIncrementType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\MinuteIncrementType
 */
class MinuteIncrementTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $data = ['increment' => 4];
        $model = new TypeTestModel(['increment' => 5]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('increment', MinuteIncrementType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'increment' => 5
        ]);

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testPresetPopulatesView(): void
    {
        $view = $this->factory->create(MinuteIncrementType::class, 3600, [])->createView();
        self::assertArrayHasKey('choices', $view->vars);
        self::assertCount(13, $view->vars['choices']);
        self::assertEquals(0, $view->vars['choices'][0]->data);
        self::assertEquals(20, $view->vars['choices'][8]->data);
    }
}
