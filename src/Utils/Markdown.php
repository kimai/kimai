<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * Parse Markdown syntax and return HTML.
 */
final class Markdown
{
    private ?ParsedownExtension $parser = null;
    private ?\Parsedown $parserFull = null;

    public function toHtml(string $text): string
    {
        if ($this->parser === null) {
            $this->parser = new ParsedownExtension();
            $this->parser->setUrlsLinked(true);
            $this->parser->setBreaksEnabled(true);
            $this->parser->setSafeMode(true);
            $this->parser->setMarkupEscaped(true);
        }

        return $this->parser->text($text);
    }

    public function withFullMarkdownSupport(string $text): string
    {
        if ($this->parserFull === null) {
            $this->parserFull = new \Parsedown();
            $this->parserFull->setUrlsLinked(true);
            $this->parserFull->setBreaksEnabled(true);
            $this->parserFull->setSafeMode(true);
            $this->parserFull->setMarkupEscaped(true);
        }

        return $this->parserFull->text($text);
    }
}
