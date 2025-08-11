<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form;

use App\Configuration\LocaleService;
use App\Entity\ExportTemplate;
use App\Form\ExportTemplateSpreadsheetForm;
use App\Form\Type\ExportColumnsType;
use App\Form\Type\LanguageType;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ExportTemplateSpreadsheetForm::class)]
class ExportTemplateSpreadsheetFormTest extends TypeTestCase
{
    /**
     * @return FormTypeInterface[]
     */
    protected function getTypes(): array // @phpstan-ignore missingType.generics
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $config = SystemConfigurationFactory::createStub();

        return [
            new ExportColumnsType($dispatcher, $translator, $config),
            new LanguageType(new LocaleService([]))
        ];
    }

    public function testWithGlobalNewActivity(): void
    {
        $model = new ExportTemplate();
        $form = $this->factory->createBuilder(ExportTemplateSpreadsheetForm::class, $model);

        $attr = $form->getFormConfig()->getOption('attr');
        self::assertIsArray($attr);
        self::assertArrayHasKey('data-form-event', $attr);
        self::assertEquals('kimai.exportTemplate', $attr['data-form-event']);

        self::assertTrue($form->has('title'));
        self::assertTrue($form->has('renderer'));
        self::assertTrue($form->has('language'));
        self::assertTrue($form->has('columns'));

        self::assertTrue($form->get('title')->getRequired());
        self::assertTrue($form->get('renderer')->getRequired());
        self::assertFalse($form->get('language')->getRequired());
        self::assertTrue($form->get('columns')->getRequired());
    }
}
