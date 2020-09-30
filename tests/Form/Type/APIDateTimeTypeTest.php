<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\APIDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\APIDateTimeType
 */
class APIDateTimeTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $data = ['date' => '2020-09-17T13:24:56'];
        $model = new TypeTestModel(['date' => new \DateTime()]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('date', APIDateTimeType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'date' => new \DateTime('2020-09-17T13:24:56')
        ]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testAttributes()
    {
        $data = ['date' => '2020-09-17T13:24:56'];
        $model = new TypeTestModel(['date' => new \DateTime()]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('date', APIDateTimeType::class, [
            'model_timezone' => 'Pacific/Tongatapu',
            'view_timezone' => 'Pacific/Tongatapu',
        ]);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'date' => new \DateTime('2020-09-17T13:24:56', new \DateTimeZone('Pacific/Tongatapu'))
        ]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }
}
