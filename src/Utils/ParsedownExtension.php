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
class ParsedownExtension extends \Parsedown
{
    private $ids = [];

    protected function blockHeader($line)
    {
        $block = parent::blockHeader($line);

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
     * @param $text
     * @return string
     */
    private function getIDfromText($text)
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
}
