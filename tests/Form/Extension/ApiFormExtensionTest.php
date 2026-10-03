<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\API\ApiFormInterface;
use App\Form\Extension\ApiFormExtension;
use App\Form\Extension\HelpTranslationDomainExtension;
use App\Form\Type\BillableType;
use App\Form\Type\YesNoType;
use Nelmio\ApiDocBundle\Form\Extension\DocumentationExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Translation\Translator;

#[CoversClass(ApiFormExtension::class)]
class ApiFormExtensionTest extends TestCase
{
    private function getSut(): ApiFormExtension
    {
        // the default locale differs on purpose: the API documentation is always english
        $translator = new Translator('de');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'help.billable' => 'Whether the record is billable',
            'help.visible' => 'Whether the record is visible',
            'help.placeholder' => 'Shown to %name%',
        ], 'en');
        $translator->addResource('array', ['help.visible' => 'Ob der Eintrag sichtbar ist'], 'de');
        $translator->addResource('array', ['help.visible' => 'Visibility from the "system" domain'], 'en', 'system');

        return new ApiFormExtension($translator);
    }

    /**
     * @return FormBuilderInterface<mixed>
     */
    private function createBuilder(): FormBuilderInterface
    {
        // the "documentation" option is provided by NelmioApiDocBundle
        return Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DocumentationExtension())
            ->addTypeExtension(new HelpTranslationDomainExtension())
            ->getFormFactory()
            ->createBuilder(FormType::class);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     */
    private function getDocumentation(FormBuilderInterface $builder, string $name): mixed
    {
        $child = $builder->get($name);
        self::assertInstanceOf(CheckboxType::class, $child->getType()->getInnerType(), $name);

        return $child->getOption('documentation');
    }

    public function testExtendedTypes(): void
    {
        self::assertEquals([ApiFormInterface::class], ApiFormExtension::getExtendedTypes());
    }

    public function testConfigureOptionsDisablesCsrfProtection(): void
    {
        $resolver = new OptionsResolver();
        $this->getSut()->configureOptions($resolver);

        self::assertEquals(['csrf_protection' => false], $resolver->resolve([]));
    }

    public function testBuildFormRemovesMetaFields(): void
    {
        $builder = $this->createBuilder();
        $builder->add('name', TextType::class);
        $builder->add('metaFields', CollectionType::class);

        $this->getSut()->buildForm($builder, []);

        self::assertTrue($builder->has('name'));
        self::assertFalse($builder->has('metaFields'));
    }

    public function testBuildFormReplacesBooleanTypesWithCheckbox(): void
    {
        $builder = $this->createBuilder();
        $builder->add('visible', YesNoType::class, [
            'label' => 'visible',
            'required' => false,
        ]);
        $builder->add('billable', BillableType::class, [
            'label' => 'billable',
            'required' => true,
            'help' => null,
        ]);

        $this->getSut()->buildForm($builder, []);

        foreach (['visible' => false, 'billable' => true] as $name => $required) {
            $child = $builder->get($name);
            self::assertInstanceOf(CheckboxType::class, $child->getType()->getInnerType(), $name);
            self::assertEquals($name, $child->getOption('label'));
            self::assertEquals($required, $child->getRequired());
            self::assertEquals([], $child->getOption('documentation'), $name);
        }
    }

    public function testBuildFormKeepsDocumentationDescription(): void
    {
        $builder = $this->createBuilder();
        $builder->add('exported', YesNoType::class, [
            'label' => 'exported',
            'help' => 'help.visible',
            'documentation' => [
                'type' => 'boolean',
                'description' => 'If true, this record was exported',
            ],
        ]);

        $this->getSut()->buildForm($builder, []);

        // an explicit description wins over the help text
        self::assertEquals(['description' => 'If true, this record was exported'], $this->getDocumentation($builder, 'exported'));
    }

    public function testBuildFormTranslatesHelpToEnglish(): void
    {
        $builder = $this->createBuilder();
        // BillableType has a default help
        $builder->add('billable', BillableType::class);
        $builder->add('visible', YesNoType::class, ['help' => 'help.visible']);
        $builder->add('parameters', YesNoType::class, [
            'help' => 'help.placeholder',
            'help_translation_parameters' => ['%name%' => 'admins'],
        ]);
        $builder->add('translatable', YesNoType::class, ['help' => new TranslatableMessage('help.visible')]);
        $builder->add('unknown', YesNoType::class, ['help' => 'help.unknown']);

        $this->getSut()->buildForm($builder, []);

        self::assertEquals(['description' => 'Whether the record is billable'], $this->getDocumentation($builder, 'billable'));
        self::assertEquals(['description' => 'Whether the record is visible'], $this->getDocumentation($builder, 'visible'));
        self::assertEquals(['description' => 'Shown to admins'], $this->getDocumentation($builder, 'parameters'));
        self::assertEquals(['description' => 'Whether the record is visible'], $this->getDocumentation($builder, 'translatable'));
        // missing translations fall back to the key, just like in the frontend
        self::assertEquals(['description' => 'help.unknown'], $this->getDocumentation($builder, 'unknown'));
    }

    public function testBuildFormUsesTranslationDomains(): void
    {
        $builder = $this->createBuilder();
        $builder->add('help_domain', YesNoType::class, [
            'help' => 'help.visible',
            'help_translation_domain' => 'system',
            'translation_domain' => 'messages',
        ]);
        $builder->add('form_domain', YesNoType::class, [
            'help' => 'help.visible',
            'translation_domain' => 'system',
        ]);
        $builder->add('disabled', YesNoType::class, [
            'help' => 'Plain help text',
            'translation_domain' => false,
        ]);

        $this->getSut()->buildForm($builder, []);

        self::assertEquals(['description' => 'Visibility from the "system" domain'], $this->getDocumentation($builder, 'help_domain'));
        self::assertEquals(['description' => 'Visibility from the "system" domain'], $this->getDocumentation($builder, 'form_domain'));
        self::assertEquals(['description' => 'Plain help text'], $this->getDocumentation($builder, 'disabled'));
    }

    public function testBuildFormDoesNotTouchOtherFields(): void
    {
        $builder = $this->createBuilder();
        $builder->add('name', TextType::class, ['label' => 'name', 'help' => 'help.visible']);
        // a plain checkbox is already API compatible and not replaced
        $builder->add('pinned', CheckboxType::class, ['documentation' => ['description' => 'foo']]);

        $this->getSut()->buildForm($builder, []);

        self::assertInstanceOf(TextType::class, $builder->get('name')->getType()->getInnerType());
        self::assertEquals('name', $builder->get('name')->getOption('label'));
        self::assertEquals([], $builder->get('name')->getOption('documentation'));
        self::assertInstanceOf(CheckboxType::class, $builder->get('pinned')->getType()->getInnerType());
        self::assertEquals(['description' => 'foo'], $builder->get('pinned')->getOption('documentation'));
    }
}
