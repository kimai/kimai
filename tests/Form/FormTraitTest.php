<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form;

use App\Form\FormTrait;
use App\Tests\Form\Type\TypeTestModel;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\FormTrait
 */
class FormTraitTest extends TypeTestCase
{
    use FormTrait;

    /**
     * @expectedDeprecation FormTrait::addDescription() is deprecated and will be removed with 2.0, use DescriptionType instead
     * @group legacy
     */
    public function testAddDescription()
    {
        $data = ['description' => 'foo'];
        $model = new TypeTestModel(['description' => 'bar']);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $this->addDescription($form);

        $desc = $form->get('description');
        self::assertArrayHasKey('autofocus', $desc->getOption('attr'));
        self::assertEquals('autofocus', $desc->getOption('attr')['autofocus']);

        $form = $form->getForm();

        $desc = $form->get('description');
        self::assertFalse($desc->isRequired());

        $expected = new TypeTestModel([
            'description' => 'foo'
        ]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }
}
