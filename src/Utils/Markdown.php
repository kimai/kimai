<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * Parse markdown syntax and return HTML.
 */
final class Markdown
{
    /**
     * @var ParsedownExtension
     */
    private $parser;

    public function toHtml(string $text, bool $safe = true): string
    {
        if ($this->parser === null) {
            $this->parser = new ParsedownExtension();
            $this->parser->setUrlsLinked(true);
            $this->parser->setBreaksEnabled(true);
        }

        if ($safe !== true) {
            @trigger_error('Only safe mode is supported in Markdown since 1.16.3 to prevent XSS attacks. Parameter $safe will be removed with 2.0', E_USER_DEPRECATED);
        }

        $this->parser->setSafeMode(true);
        $this->parser->setMarkupEscaped(true);

        return $this->parser->text($text);
    }

    public function withFullMarkdownSupport(string $text): string
    {
        $parser = new \Parsedown();
        $parser->setUrlsLinked(true);
        $parser->setBreaksEnabled(true);
        $parser->setSafeMode(true);
        $parser->setMarkupEscaped(true);

        return $parser->text($text);
    }
}
