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

    protected function blockHeader($Line)
    {
        $block = parent::blockHeader($Line);

        $text = $block['element']['text'];

        if (\is_string($text) && $text !== '') {
            $id = $this->getIDfromText($text);

            // add id-attribute
            $block['element']['attributes'] = [
                'id' => $id
            ];
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
}
