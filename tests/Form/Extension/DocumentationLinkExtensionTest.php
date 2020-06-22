<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\Extension\DocumentationLinkExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @covers \App\Form\Extension\DocumentationLinkExtension
 */
class DocumentationLinkExtensionTest extends TestCase
{
    public function testExtendedTypes()
    {
        self::assertEquals([FormType::class], DocumentationLinkExtension::getExtendedTypes());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $sut = new DocumentationLinkExtension();
        $sut->configureOptions($resolver);
        self::assertEquals(['docu_chapter'], $resolver->getDefinedOptions());
        self::assertTrue($resolver->hasDefault('docu_chapter'));
        self::assertFalse($resolver->isRequired('docu_chapter'));

        $result = $resolver->resolve(['docu_chapter' => 'foo']);
        self::assertEquals(['docu_chapter' => 'foo'], $result);

        $result = $resolver->resolve([]);
        self::assertEquals(['docu_chapter' => ''], $result);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['docu_chapter' => true]);
    }
}
