<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Helper;

use App\Configuration\SystemConfiguration;
use App\Entity\Project;
use App\Utils\LocaleSettings;

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

    private $configuration;
    private $localeSettings;
    private $dateFormat;
    private $pattern;

    public function __construct(SystemConfiguration $configuration, LocaleSettings $localeSettings)
    {
        $this->configuration = $configuration;
        $this->localeSettings = $localeSettings;
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
        if ($this->dateFormat === null) {
            $this->dateFormat = $this->localeSettings->getDateFormat();
        }

        $start = '?';
        if ($project->getStart() !== null) {
            $start = $project->getStart()->format($this->dateFormat);
        }

        $end = '?';
        if ($project->getEnd() !== null) {
            $end = $project->getEnd()->format($this->dateFormat);
        }

        $name = $this->getChoicePattern();
        $name = str_replace(self::PATTERN_NAME, $project->getName(), $name);
        $name = str_replace(self::PATTERN_COMMENT, $project->getComment() ?? '', $name);
        $name = str_replace(self::PATTERN_CUSTOMER, $project->getCustomer()->getName() ?? '', $name);
        $name = str_replace(self::PATTERN_ORDERNUMBER, $project->getOrderNumber() ?? '', $name);
        $name = str_replace(self::PATTERN_START, $start, $name);
        $name = str_replace(self::PATTERN_END, $end, $name);

        $name = ltrim($name, self::SPACER);
        $name = rtrim($name, self::SPACER);
        $name = str_replace('- ?-?', '', $name);

        if ($name === '' || $name === self::SPACER) {
            $name = $project->getName();
        }

        return substr($name, 0, 110);
    }
}
