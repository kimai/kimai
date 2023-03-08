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
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class LanguageTypeTest extends TypeTestCase
{
    public function testNaturalSortOfLanguageDisplayNames(): void
    {
        $form = $this->factory->create(LanguageType::class);

        $options = $form->getConfig()->getOptions();

        $this->assertArrayHasKey('choices', $options);
        $this->assertIsArray($options['choices']);

        $choices = array_values($options['choices']);

        $this->assertEquals(
            expected: ['de', 'de_CH', 'de_AT', 'en'],
            actual: $choices,
            message: 'Natural order of language names is not correct'
        );
    }

    /**
     * @return iterable<FormExtensionInterface>
     */
    protected function getExtensions(): iterable
    {
        $languageSettings = [
            'de' => [
                'date' => 'dd.MM.yy',
                'time' => 'HH:mm',
                'rtl' => false,
            ],
            'de_AT' => [
                'date' => 'dd.MM.yy',
                'time' => 'HH:mm',
                'rtl' => false,
            ],
            'de_CH' => [
                'date' => 'dd.MM.yy',
                'time' => 'HH:mm',
                'rtl' => false,
            ],
            'en' => [
                'date' => 'M/d/yy',
                'time' => 'h:mm a',
                'rtl' => false,
            ]
        ];

        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([
                new LanguageType(new LocaleService($languageSettings))
            ], [])
        ]);
    }
}
