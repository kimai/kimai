<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * This Class extends the default Parsedown Class for custom methods.
 */
final class ParsedownExtension extends Parsedown
{
    /**
     * Overwritten to prevent # and = to show up as headings for two reasons:
     * - Hashes are often used to cross-link issues in other systems
     * - Headings should not occur in time record listings
     */
    protected $BlockTypes = [
        '*' => ['Rule', 'List'],
        '+' => ['List'],
        '-' => ['Table', 'Rule', 'List'],
        '0' => ['List'],
        '1' => ['List'],
        '2' => ['List'],
        '3' => ['List'],
        '4' => ['List'],
        '5' => ['List'],
        '6' => ['List'],
        '7' => ['List'],
        '8' => ['List'],
        '9' => ['List'],
        ':' => ['Table'],
        '<' => ['Comment', 'Markup'],
        '>' => ['Quote'],
        '[' => ['Reference'],
        '_' => ['Rule'],
        '`' => ['FencedCode'],
        '|' => ['Table'],
        '~' => ['FencedCode'],
    ];

    /**
     * Overwritten to open links in new windows
     */
    protected function inlineUrl($Excerpt): ?array
    {
        $block = parent::inlineUrl($Excerpt);

        if (isset($block['element']['attributes']) && \is_array($block['element']['attributes'])) {
            $block['element']['attributes']['target'] = '_blank';
        }

        return $block;
    }

    protected function blockTable($Line, ?array $Block = null) // @phpstan-ignore missingType.return,missingType.iterableValue,missingType.parameter
    {
        $Block = parent::blockTable($Line, $Block);

        if ($Block === null) {
            return null;
        }

        $Block['element']['attributes']['class'] = 'table';

        return $Block;
    }
}
