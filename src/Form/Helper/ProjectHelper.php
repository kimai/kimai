<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Helper;

use App\Configuration\LocaleService;
use App\Configuration\SystemConfiguration;
use App\Entity\Project;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectHelper
{
    public const PATTERN_NAME = '{name}';
    public const PATTERN_COMMENT = '{comment}';
    public const PATTERN_ORDERNUMBER = '{ordernumber}';
    public const PATTERN_DATERANGE = '{daterange}';
    public const PATTERN_START = '{start}';
    public const PATTERN_END = '{end}';
    public const PATTERN_CUSTOMER = '{parentTitle}';
    public const PATTERN_SPACER = '{spacer}';
    public const SPACER = ' - ';

    private ?\IntlDateFormatter $dateFormatter = null;
    private ?string $pattern = null;
    private bool $showStart = false;
    private bool $showEnd = false;

    public function __construct(private SystemConfiguration $configuration, private LocaleService $localeService, private TranslatorInterface $translator)
    {
    }

    public function getChoicePattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->configuration->find('project.choice_pattern');

            if ($this->pattern === null || stripos($this->pattern, '{') === false || stripos($this->pattern, '}') === false) {
                $this->pattern = self::PATTERN_NAME;
            }

            $this->pattern = str_replace(self::PATTERN_DATERANGE, self::PATTERN_START . '-' . self::PATTERN_END, $this->pattern);
            $this->pattern = str_replace(self::PATTERN_SPACER, self::SPACER, $this->pattern);
        }

        return $this->pattern;
    }

    public function getChoiceLabel(Project $project): string
    {
        $name = $this->getChoicePattern();
        $name = str_replace(self::PATTERN_NAME, $project->getName(), $name);
        $name = str_replace(self::PATTERN_COMMENT, $project->getComment() ?? '', $name);
        $name = str_replace(self::PATTERN_CUSTOMER, $project->getCustomer()->getName() ?? '', $name);
        $name = str_replace(self::PATTERN_ORDERNUMBER, $project->getOrderNumber() ?? '', $name);

        if ($this->dateFormatter === null) {
            $this->showStart = stripos($name, self::PATTERN_START) !== false;
            $this->showEnd = stripos($name, self::PATTERN_END) !== false;
            $this->dateFormatter = new \IntlDateFormatter(
                \Locale::getDefault(),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                \IntlDateFormatter::GREGORIAN,
                $this->localeService->getDateFormat(\Locale::getDefault())
            );
        }

        if ($this->showStart) {
            $start = '';
            if ($project->getStart() !== null) {
                $start = $this->translator->trans('project_start') . ': ' . $this->dateFormatter->format($project->getStart()) . ' ';
            }
            $name = str_replace(self::PATTERN_START, $start, $name);
        }

        if ($this->showEnd) {
            $end = '';
            if ($project->getEnd() !== null) {
                $end = ' ' . $this->translator->trans('project_end') . ': ' . $this->dateFormatter->format($project->getEnd());
            }
            $name = str_replace(self::PATTERN_END, $end, $name);
        }

        $name = ltrim($name, self::SPACER);
        $name = rtrim($name, self::SPACER);
        $name = str_replace('- ?-?', '', $name);

        if ($name === '' || $name === self::SPACER) {
            $name = $project->getName();
        }

        return substr($name, 0, 110);
    }
}
