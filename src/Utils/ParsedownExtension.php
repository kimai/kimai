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
    /** @var array<string> */
    private array $ids = [];

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

    protected function blockHeader($Line)
    {
        $block = parent::blockHeader($Line);

        $text = $block['element']['text'];
        $id = $this->getIDfromText($text);

        // add id-attribute
        $block['element']['attributes'] = [
            'id' => $id
        ];

        return $block;
    }

    /**
     * github-action for creating ids:
     *
     * - It downcases the header string
     * - remove anything that is not a letter, number, space or hyphen
     * - changes any space to a hyphen.
     * - If that is not unique, add "-1", "-2", "-3",... to make it unique
     *
     * @param string $text
     * @return string
     */
    private function getIDfromText($text): string
    {
        $text = strtolower($text);

        $text = preg_replace('/[^A-Za-z0-9\-\ ]/', '', $text);
        $text = strtr($text, [' ' => '-']);

        if (isset($this->ids[$text])) {
            $i = 0;
            $numberedText = $text . '-1';

            while (isset($this->ids[$numberedText])) {
                $i++;
                $numberedText = $text . '-' . $i;
            }

            $text = $numberedText;
        }

        $this->ids[$text] = '';

        return $text;
    }

    protected function blockTable($Line, array $Block = null)
    {
        $Block = parent::blockTable($Line, $Block);

        if ($Block === null) {
            return null;
        }

        $Block['element']['attributes']['class'] = 'table';

        return $Block;
    }
}
