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
class Parsedown extends \Parsedown
{
    /** @var array<string> */
    private array $ids = [];

    /**
     * Overwritten to open links in new windows
     */
    protected function inlineUrl($Excerpt): ?array // @phpstan-ignore missingType.parameter,missingType.iterableValue
    {
        $block = parent::inlineUrl($Excerpt);

        if (isset($block['element']['attributes']) && \is_array($block['element']['attributes'])) {
            $block['element']['attributes']['target'] = '_blank';
        }

        return $block;
    }

    protected function blockHeader($Line)
    {
        $block = parent::blockHeader($Line);

        if (isset($block['element']['handler']['argument'])) {
            $text = $block['element']['handler']['argument'];

            if (\is_string($text) && $text !== '') {
                $id = $this->getIDfromText($text);

                // add id-attribute
                $block['element']['attributes'] = [
                    'id' => $id
                ];
            }
        }

        return $block;
    }

    /**
     * github-action for creating ids:
     *
     * - It downcases the header string
     * - Removes anything that is not a letter, number, space or hyphen
     * - Changes any space to a hyphen
     * - If that is not unique, add "-1", "-2", "-3",... to make it unique
     * #
     * @param non-empty-string $text
     * @return string
     */
    private function getIDfromText(string $text): string
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

    protected function blockTable($Line, ?array $Block = null) // @phpstan-ignore missingType.return,missingType.iterableValue,missingType.parameter
    {
        $Block = parent::blockTable($Line, $Block);

        if ($Block === null) {
            return null;
        }

        $Block['element']['attributes']['class'] = 'table table-striped table-vcenter';

        return $Block;
    }

    /**
     * Markdown image syntax `![alt](url)` is rewritten to a link `<a href="url">alt</a>`.
     *
     * Rationale: emitting `<img src="url">` would cause downstream renderers
     * (e.g. mPDF on the server, browsers in the UI) to automatically fetch
     * the remote URL. For server-side renderers this is a server-side request
     * forgery vector; in the UI it is a tracking/privacy issue. Hand-written
     * `<img>` in Twig templates (custom invoice templates etc.) is not
     * affected — only images derived from Markdown input are neutralised
     * here. The resulting `<a href>` is still passed through Parsedown's
     * `safeLinksWhitelist` filtering when safe-mode is enabled.
     *
     * @see https://github.com/kimai/kimai/security/advisories/GHSA-pj8j-p4g4-4vw8
     */
    protected function inlineImage($Excerpt): ?array // @phpstan-ignore missingType.parameter,missingType.iterableValue
    {
        $Image = parent::inlineImage($Excerpt);

        if ($Image === null) {
            return null;
        }

        $src = $Image['element']['attributes']['src'] ?? '';
        $alt = $Image['element']['attributes']['alt'] ?? '';

        $Image['element'] = [
            'name' => 'a',
            'text' => $alt !== '' ? $alt : $src,
            'attributes' => [
                'href' => $src,
                'rel' => 'noopener noreferrer',
                'target' => '_blank',
            ],
        ];

        return $Image;
    }
}
