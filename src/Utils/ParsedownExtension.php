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
     * Overwritten to add support for file:///
     */
    protected $safeLinksWhitelist = [
        'file:///',
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    ];

    /**
     * Overwritten:
     * - added support for file:///
     * - open links in new windows
     */
    protected function inlineUrl($Excerpt): ?array
    {
        if ($this->urlsLinked !== true or !isset($Excerpt['text'][2]) or $Excerpt['text'][2] !== '/') {
            return null;
        }

        if (preg_match('/\b(https?:[\/]{2}|file:[\/]{3})[^\s<]+\b\/*/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE)) {
            $url = $matches[0][0];

            return [
                'extent' => \strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                        'target' => '_blank'
                    ],
                ],
            ];
        }

        return null;
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
