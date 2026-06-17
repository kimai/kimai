<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Model;

use App\Form\Model\Configuration;
use App\Form\Type\YesNoType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Configuration('foo');

        self::assertSame('foo', $sut->getName());
        self::assertNull($sut->getLabel());
        self::assertSame('messages', $sut->getTranslationDomain());
        self::assertNull($sut->getValue());
        self::assertNull($sut->getType());
        self::assertSame([], $sut->getOptions());
        self::assertTrue($sut->isEnabled());
        self::assertTrue($sut->isRequired());
        self::assertNull($sut->getFormTheme());
        self::assertSame([], $sut->getConstraints());
    }

    public function testFluentSetterAndGetter(): void
    {
        $sut = new Configuration('foo');
        $constraints = [new NotBlank()];
        $options = ['attr' => ['data-test' => 'value'], 'empty_data' => 0];

        $result = $sut
            ->setLabel('Foo label')
            ->setTranslationDomain('admin')
            ->setType('custom-type')
            ->setValue(42.5)
            ->setOptions($options)
            ->setConstraints($constraints)
            ->setEnabled(false)
            ->setRequired(false)
            ->setFormTheme('@MyBundle/form/test.html.twig');

        self::assertSame($sut, $result);
        self::assertSame('Foo label', $sut->getLabel());
        self::assertSame('admin', $sut->getTranslationDomain());
        self::assertSame('custom-type', $sut->getType());
        self::assertSame(42.5, $sut->getValue());
        self::assertSame($options, $sut->getOptions());
        self::assertSame($constraints, $sut->getConstraints());
        self::assertFalse($sut->isEnabled());
        self::assertFalse($sut->isRequired());
        self::assertSame('@MyBundle/form/test.html.twig', $sut->getFormTheme());
    }

    #[DataProvider('provideScalarValues')]
    public function testSetValueKeepsOriginalTypeForNonBooleanField(string|int|null|bool|float $value): void
    {
        $sut = new Configuration('foo');

        self::assertSame($sut, $sut->setValue($value));
        self::assertSame($value, $sut->getValue());
    }

    /**
     * @return iterable<string, array{0: string, 1: string|int|null|bool|float, 2: bool}>
     */
    public static function provideBooleanTypeValues(): iterable
    {
        yield 'checkbox true string' => [CheckboxType::class, '1', true];
        yield 'checkbox zero string' => [CheckboxType::class, '0', false];
        yield 'checkbox integer zero' => [CheckboxType::class, 0, false];
        yield 'checkbox integer one' => [CheckboxType::class, 1, true];
        yield 'checkbox null' => [CheckboxType::class, null, false];
        yield 'checkbox empty string' => [CheckboxType::class, '', false];
        yield 'yes no text value' => [YesNoType::class, 'yes', true];
        yield 'yes no false bool' => [YesNoType::class, false, false];
        yield 'yes no float' => [YesNoType::class, 3.14, true];
    }

    #[DataProvider('provideBooleanTypeValues')]
    public function testSetValueCastsToBoolForBooleanTypes(string $type, string|int|null|bool|float $value, bool $expected): void
    {
        $sut = new Configuration('foo');

        self::assertSame($sut, $sut->setType($type));
        self::assertSame($sut, $sut->setValue($value));
        self::assertSame($expected, $sut->getValue());
    }

    public function testChangingTypeAfterSettingValueDoesNotRetroactivelyCastValue(): void
    {
        $sut = new Configuration('foo');

        $sut->setValue('1');
        $sut->setType(CheckboxType::class);

        self::assertSame('1', $sut->getValue());
    }

    /**
     * @return iterable<string, array{0: string|int|null|bool|float}>
     */
    public static function provideScalarValues(): iterable
    {
        yield 'null' => [null];
        yield 'string' => ['hello world'];
        yield 'integer' => [123];
        yield 'float' => [123.45];
        yield 'true' => [true];
        yield 'false' => [false];
    }
}
