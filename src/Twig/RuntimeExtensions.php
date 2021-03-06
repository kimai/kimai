<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Twig\Runtime\EncoreExtension;
use App\Twig\Runtime\ExporterExtension;
use App\Twig\Runtime\MarkdownExtension;
use App\Twig\Runtime\ReportingExtension;
use App\Twig\Runtime\ThemeExtension;
use App\Twig\Runtime\TimesheetExtension;
use App\Twig\Runtime\WidgetExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RuntimeExtensions extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('trigger', [ThemeExtension::class, 'trigger'], ['needs_environment' => true]),
            new TwigFunction('javascript_translations', [ThemeExtension::class, 'getJavascriptTranslations']),
            new TwigFunction('timesheet_exporter', [ExporterExtension::class, 'getTimesheetExporter']),
            new TwigFunction('active_timesheets', [TimesheetExtension::class, 'activeEntries']),
            new TwigFunction('encore_entry_css_source', [EncoreExtension::class, 'getEncoreEntryCssSource']),
            new TwigFunction('render_widget', [WidgetExtension::class, 'renderWidget'], ['is_safe' => ['html']]),
            new TwigFunction('available_reports', [ReportingExtension::class, 'getAvailableReports'], []),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('md2html', [MarkdownExtension::class, 'markdownToHtml'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFilter('desc2html', [MarkdownExtension::class, 'timesheetContent'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFilter('comment2html', [MarkdownExtension::class, 'commentContent'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
        ];
    }
}
