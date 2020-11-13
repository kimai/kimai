<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\Extension\EnhancedChoiceTypeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @covers \App\Form\Extension\EnhancedChoiceTypeExtension
 */
class EnhancedChoiceTypeExtensionTest extends TestCase
{
    public function testExtendedTypes()
    {
        self::assertEquals([EntityType::class, ChoiceType::class], EnhancedChoiceTypeExtension::getExtendedTypes());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $sut = new EnhancedChoiceTypeExtension();
        $sut->configureOptions($resolver);
        self::assertEquals(['selectpicker', 'width', 'search'], $resolver->getDefinedOptions());
        self::assertTrue($resolver->hasDefault('selectpicker'));
        self::assertTrue($resolver->hasDefault('width'));
        self::assertTrue($resolver->hasDefault('search'));
        self::assertFalse($resolver->isRequired('selectpicker'));
        self::assertFalse($resolver->isRequired('width'));
        self::assertFalse($resolver->isRequired('search'));

        $result = $resolver->resolve([]);
        self::assertEquals(['selectpicker' => true, 'width' => '100%', 'search' => true], $result);
    }
}
