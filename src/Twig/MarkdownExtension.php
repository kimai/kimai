<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\Markdown;
use Twig\TwigFilter;

/**
 * A twig extension to handle markdown parser.
 */
class MarkdownExtension extends \Twig_Extension
{
    /**
     * @var Markdown
     */
    private $markdown;

    /**
     * MarkdownExtension constructor.
     * @param Markdown $parser
     */
    public function __construct(Markdown $parser)
    {
        $this->markdown = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('md2html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Transforms the given Markdown content into HTML content.
     */
    public function markdownToHtml(string $content): string
    {
        return $this->markdown->toHtml($content);
    }
}
