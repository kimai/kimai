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
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ExportColumnsType::class)]
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
        $config = SystemConfigurationFactory::createStub();

        return [
            new ExportColumnsType($dispatcher, $translator, $config)
        ];
    }

    /**
     * @param array<mixed> $value
     * @param array<mixed> $expected
     */
    #[DataProvider('getTestData')]
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

    public function testViewKeepsOrderOfSelectedColumns(): void
    {
        $columns = ['project.name', 'user.name', 'duration', 'hourly_rate', 'rate'];
        $model = new TypeTestModel(['columns' => $columns]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('columns', ExportColumnsType::class);
        $view = $form->getForm()->createView();

        $selected = [];
        foreach ($view['columns']->vars['choices'] as $choice) {
            $choices = $choice instanceof ChoiceGroupView ? $choice->choices : [$choice];
            foreach ($choices as $item) {
                self::assertInstanceOf(ChoiceView::class, $item);
                if (\in_array($item->value, $columns, true)) {
                    $selected[] = $item->value;
                }
            }
        }

        self::assertEquals($columns, $selected);
    }
}
