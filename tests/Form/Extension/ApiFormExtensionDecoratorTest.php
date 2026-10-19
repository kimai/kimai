<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\API\ApiFormInterface;
use App\Form\API\InvoiceApiEditForm;
use App\Form\API\TagApiEditForm;
use App\Form\Extension\ApiFormExtension;
use App\Form\Extension\ApiFormExtensionDecorator;
use App\Form\InvoiceEditForm;
use App\Form\TagEditForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ApiFormExtensionDecorator::class)]
class ApiFormExtensionDecoratorTest extends KernelTestCase
{
    public function testDelegatesToInnerExtension(): void
    {
        $type = new TextType();
        $guesser = $this->createMock(FormTypeGuesserInterface::class);

        $inner = $this->createMock(FormExtensionInterface::class);
        $inner->expects($this->once())->method('getType')->with(TextType::class)->willReturn($type);
        $inner->expects($this->once())->method('hasType')->with(TextType::class)->willReturn(true);
        $inner->expects($this->once())->method('getTypeGuesser')->willReturn($guesser);

        $sut = new ApiFormExtensionDecorator($inner, new ApiFormExtension($this->createMock(TranslatorInterface::class)));

        self::assertSame($type, $sut->getType(TextType::class));
        self::assertTrue($sut->hasType(TextType::class));
        self::assertSame($guesser, $sut->getTypeGuesser());
    }

    public function testDoesNotExtendOtherTypes(): void
    {
        $extension = $this->createMock(FormTypeExtensionInterface::class);

        $inner = $this->createMock(FormExtensionInterface::class);
        $inner->method('getTypeExtensions')->willReturnMap([
            [TextType::class, [$extension]],
            [InvoiceEditForm::class, []],
        ]);
        $inner->method('hasTypeExtensions')->willReturnMap([
            [TextType::class, true],
            [InvoiceEditForm::class, false],
        ]);

        $sut = new ApiFormExtensionDecorator($inner, new ApiFormExtension($this->createMock(TranslatorInterface::class)));

        self::assertSame([$extension], $sut->getTypeExtensions(TextType::class));
        self::assertTrue($sut->hasTypeExtensions(TextType::class));

        // the parent type of an API form is not an API form itself
        self::assertSame([], $sut->getTypeExtensions(InvoiceEditForm::class));
        self::assertFalse($sut->hasTypeExtensions(InvoiceEditForm::class));
    }

    public function testExtendsApiForms(): void
    {
        $extension = $this->createMock(FormTypeExtensionInterface::class);
        $apiFormExtension = new ApiFormExtension($this->createMock(TranslatorInterface::class));

        $inner = $this->createMock(FormExtensionInterface::class);
        $inner->method('getTypeExtensions')->willReturnMap([
            [InvoiceApiEditForm::class, []],
            [ApiFormTestType::class, [$extension]],
        ]);
        $inner->method('hasTypeExtensions')->willReturn(false);

        $sut = new ApiFormExtensionDecorator($inner, $apiFormExtension);

        self::assertSame([$apiFormExtension], $sut->getTypeExtensions(InvoiceApiEditForm::class));
        self::assertTrue($sut->hasTypeExtensions(InvoiceApiEditForm::class));

        // e.g. a plugin form type, existing extensions are kept
        self::assertSame([$extension, $apiFormExtension], $sut->getTypeExtensions(ApiFormTestType::class));
        self::assertTrue($sut->hasTypeExtensions(ApiFormTestType::class));
    }

    /**
     * API forms must not use CSRF protection, while the frontend forms they are based on still do.
     */
    public function testIsRegisteredInFormRegistry(): void
    {
        /** @var FormRegistryInterface $registry */
        $registry = self::getContainer()->get('form.registry');

        foreach ([InvoiceApiEditForm::class => InvoiceEditForm::class, TagApiEditForm::class => TagEditForm::class] as $apiForm => $frontendForm) {
            $apiType = $registry->getType($apiForm);
            $extensions = array_map(fn (FormTypeExtensionInterface $extension) => $extension::class, $apiType->getTypeExtensions());
            self::assertContains(ApiFormExtension::class, $extensions, $apiForm);
            self::assertFalse($apiType->getOptionsResolver()->resolve()['csrf_protection'], $apiForm);

            $frontendType = $registry->getType($frontendForm);
            $extensions = array_map(fn (FormTypeExtensionInterface $extension) => $extension::class, $frontendType->getTypeExtensions());
            self::assertNotContains(ApiFormExtension::class, $extensions, $frontendForm);
            self::assertTrue($frontendType->getOptionsResolver()->resolve()['csrf_protection'], $frontendForm);
        }
    }
}

/**
 * @extends AbstractType<mixed>
 */
class ApiFormTestType extends AbstractType implements ApiFormInterface
{
}
