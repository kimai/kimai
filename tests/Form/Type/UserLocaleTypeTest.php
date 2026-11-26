<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Configuration\LocaleService;
use App\Form\Type\LanguageType;
use App\Form\Type\UserLocaleType;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(UserLocaleType::class)]
class UserLocaleTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $localeService = new LocaleService([
            'en' => ['date' => 'M/d/y', 'time' => 'h:mm a', 'rtl' => false, 'translation' => true],
            'en_AU' => ['date' => 'd/M/y', 'time' => 'h:mm a', 'rtl' => false, 'translation' => false],
            'en_GB' => ['date' => 'dd/MM/y', 'time' => 'HH:mm', 'rtl' => false, 'translation' => false],
            'de' => ['date' => 'dd.MM.y', 'time' => 'HH:mm', 'rtl' => false, 'translation' => true],
        ]);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/help/locales');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return [
            new PreloadedExtension([
                new LanguageType($localeService),
                new UserLocaleType($router, $translator, $localeService, SystemConfigurationFactory::createStub([
                    'defaults' => ['customer' => ['currency' => 'EUR']],
                ])),
            ], []),
        ];
    }

    public function testChoiceLabelsContainFormattingExample(): void
    {
        $form = $this->factory->createBuilder(FormType::class, new TypeTestModel(['locale' => null]));
        $form->add('locale', UserLocaleType::class);
        $view = $form->getForm()->createView();

        $labels = [];
        /** @var ChoiceView $choice */
        foreach ($view['locale']->vars['choices'] as $choice) {
            $labels[$choice->value] = $choice->label;
        }

        // the base locale looks like the regional one, but formats like en_US
        self::assertEquals('English – 12/24/2025, 2:30 PM, €1,234.56', $labels['en']);
        self::assertEquals("English (Australia) – 24/12/2025, 2:30 pm, EUR\u{a0}1,234.56", $labels['en_AU']);
        self::assertEquals('English (United Kingdom) – 24/12/2025, 14:30, €1,234.56', $labels['en_GB']);
        self::assertEquals("Deutsch – 24.12.2025, 14:30, 1.234,56\u{a0}€", $labels['de']);
    }

    public function testSubmitValidData(): void
    {
        $model = new TypeTestModel(['locale' => 'en']);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('locale', UserLocaleType::class);
        $form = $form->getForm();
        $form->submit(['locale' => 'en_AU']);

        self::assertTrue($form->isSynchronized());
        self::assertEquals('en_AU', $model->offsetGet('locale'));
    }
}
