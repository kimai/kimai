<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\TimesheetConfiguration;
use App\Utils\Markdown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * A twig extension to handle markdown parser.
 */
class MarkdownExtension extends AbstractExtension
{
    /**
     * @var Markdown
     */
    private $markdown;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    /**
     * MarkdownExtension constructor.
     * @param Markdown $parser
     */
    public function __construct(Markdown $parser, TimesheetConfiguration $configuration)
    {
        $this->markdown = $parser;
        $this->configuration = $configuration;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('md2html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
            new TwigFilter('desc2html', [$this, 'timesheetContent'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Transforms the timesheet description content into HTML.
     *
     * @param string $content
     * @return string
     */
    public function timesheetContent($content): string
    {
        if (empty($content)) {
            return '';
        }

        if ($this->configuration->isMarkdownEnabled()) {
            return $this->markdown->toHtml($content, false);
        }

        return nl2br($content);
    }

    /**
     * Transforms the given Markdown content into HTML
     *
     * @param string $content
     * @return string
     */
    public function markdownToHtml(string $content): string
    {
        return $this->markdown->toHtml($content, true);
    }
}
