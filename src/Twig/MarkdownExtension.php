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
 * A twig extension to handle markdown content.
 */
final class MarkdownExtension extends AbstractExtension
{
    /**
     * @var Markdown
     */
    private $markdown;
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;

    /**
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
            new TwigFilter('comment2html', [$this, 'commentContent'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Transforms the entities comment (customer, project, activity ...) into HTML.
     *
     * @param string $content
     * @param bool $fullLength
     * @return string
     */
    public function commentContent(?string $content, bool $fullLength = false): string
    {
        if (empty($content)) {
            return '';
        }

        if (!$fullLength && \strlen($content) > 101) {
            $content = trim(substr($content, 0, 100)) . ' &hellip;';
        }

        if ($this->configuration->isMarkdownEnabled()) {
            $content = $this->markdown->toHtml($content, false);
        } elseif ($fullLength) {
            $content = '<p>' . nl2br($content) . '</p>';
        }

        return $content;
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
        return $this->markdown->toHtml($content, false);
    }
}
