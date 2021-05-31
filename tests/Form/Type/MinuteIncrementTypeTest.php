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
    public function testSubmitValidData()
    {
        $data = ['increment' => 4];
        $model = new TypeTestModel(['increment' => 5]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('increment', MinuteIncrementType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'increment' => '3'
        ]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testSubmitValidDataWithoutDeactivate()
    {
        $data = ['increment' => 4];
        $model = new TypeTestModel(['increment' => 5]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('increment', MinuteIncrementType::class, ['deactivate' => false]);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'increment' => '4'
        ]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testPresetPopulatesView()
    {
        $view = $this->factory->create(MinuteIncrementType::class, 3600, [])->createView();
        self::assertArrayHasKey('choices', $view->vars);
        self::assertCount(16, $view->vars['choices']);
        self::assertEquals(null, $view->vars['choices'][0]->data);
        self::assertEquals(0, $view->vars['choices'][1]->data);
        self::assertEquals(1, $view->vars['choices'][2]->data);
    }

    public function testPresetPopulatesViewWithoutDeactivate()
    {
        $view = $this->factory->create(MinuteIncrementType::class, 3600, ['deactivate' => false])->createView();
        self::assertArrayHasKey('choices', $view->vars);
        self::assertCount(15, $view->vars['choices']);
        self::assertEquals(null, $view->vars['choices'][0]->data);
        self::assertEquals(1, $view->vars['choices'][1]->data);
        self::assertEquals(2, $view->vars['choices'][2]->data);
    }
}
