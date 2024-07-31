<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class TranslationsTest extends TestCase
{
    public function testForWrongFileExtension(): void
    {
        $files = glob(__DIR__ . '/../translations/*.*');
        foreach ($files as $file) {
            self::assertStringEndsWith('.xlf', $file);
        }
    }

    public function testForEmptyStrings(): void
    {
        $files = glob(__DIR__ . '/../translations/*.xlf');
        foreach ($files as $file) {
            $xml = simplexml_load_file($file);

            /** @var \SimpleXMLElement $body */
            $body = $xml->file->body;

            foreach ($body->children() as $transUnit) {
                self::assertNotEmpty(
                    (string) $transUnit->target,
                    sprintf(
                        'Found empty translation in language "%s" and file "%s" for key "%s"',
                        $xml->file->attributes()['target-language'],
                        basename($file),
                        (string) $transUnit->source
                    )
                );
            }
        }
    }

    public function testReplacerWereNotTranslated(): void
    {
        $englishFiles = glob(__DIR__ . '/../translations/*.en.xlf');
        foreach ($englishFiles as $englishFile) {
            $english = simplexml_load_file($englishFile);
            $trans = [];
            /** @var \SimpleXMLElement $body */
            $body = $english->file->body;

            foreach ($body->children() as $transUnit) {
                preg_match_all('/%[a-zA-Z]{1,}%/Uu', (string) $transUnit->target, $matches);
                if (!empty($matches) && !empty($matches[0])) {
                    asort($matches[0]);
                    $trans[(string) $transUnit->source] = array_values($matches[0]);
                }
            }

            if (empty($trans)) {
                continue;
            }

            $expectedCounter = \count($trans);
            $files = glob(__DIR__ . '/../translations/' . str_replace('.en.xlf', '', basename($englishFile)) . '*.xlf');
            foreach ($files as $file) {
                if ($englishFile === $file) {
                    continue;
                }

                $counter = 0;
                $xml = simplexml_load_file($file);
                $transLang = $trans;

                /** @var \SimpleXMLElement $body */
                $body = $xml->file->body;

                foreach ($body->children() as $transUnit) {
                    $key = (string) $transUnit->source;
                    if (isset($transLang[$key])) {
                        // some special cases, which don't work properly - base translation should be changed
                        preg_match_all('/%[a-zA-Z]{1,}%/Uu', (string) $transUnit->target, $matches);
                        asort($matches[0]);
                        self::assertEquals($transLang[$key], array_values($matches[0]), sprintf('Invalid replacer "%s" in "%s"', $key, basename($file)));
                        $counter++;
                        unset($transLang[$key]);
                    }
                }

                $counter += \count($transLang);
                self::assertEquals($expectedCounter, $counter, sprintf('Missing replacer in "%s", did not find translation keys: %s', basename($file), implode(', ', array_keys($transLang))));
            }
        }
    }
}
