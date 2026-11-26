<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\API;

use App\Form\API\DateApiType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(DateApiType::class)]
class DateApiTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $data = ['date' => '2024-05-17'];
        $model = ['date' => null];

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('date', DateApiType::class);
        $form = $form->getForm();

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        $date = $form->get('date')->getData();
        self::assertInstanceOf(\DateTime::class, $date);
        self::assertEquals('2024-05-17', $date->format('Y-m-d'));
    }

    public function testOptionsAreSetCorrectly(): void
    {
        $view = $this->factory->create(DateApiType::class)->createView();

        self::assertSame('single_text', $view->vars['widget']);
        self::assertArrayHasKey('type', $view->vars);
        self::assertEquals('date', $view->vars['type']);
    }

    public function testDocumentationOption(): void
    {
        $form = $this->factory->create(DateApiType::class);
        $config = $form->getConfig();
        self::assertTrue($config->getOption('html5'));

        self::assertTrue($config->hasOption('documentation'));
        $documentation = $config->getOption('documentation');
        self::assertIsArray($documentation);

        self::assertEquals('string', $documentation['type']);
        self::assertEquals('date', $documentation['format']);
        self::assertIsString($documentation['example']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $documentation['example']);
    }
}
