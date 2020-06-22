<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\Extension\IconExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @covers \App\Form\Extension\IconExtension
 */
class IconExtensionTest extends TestCase
{
    public function testExtendedTypes()
    {
        self::assertEquals([TextType::class], IconExtension::getExtendedTypes());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $sut = new IconExtension();
        $sut->configureOptions($resolver);
        self::assertEquals(['icon'], $resolver->getDefinedOptions());
        self::assertTrue($resolver->hasDefault('icon'));
        self::assertFalse($resolver->isRequired('icon'));

        $result = $resolver->resolve(['icon' => 'foo']);
        self::assertEquals(['icon' => 'foo'], $result);

        $result = $resolver->resolve([]);
        self::assertEquals(['icon' => ''], $result);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['icon' => true]);
    }
}
