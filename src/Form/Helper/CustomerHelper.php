<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Helper;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer;

final class CustomerHelper
{
    public const PATTERN_NAME = '{name}';
    public const PATTERN_NUMBER = '{number}';
    public const PATTERN_COMPANY = '{company}';
    public const PATTERN_COMMENT = '{comment}';
    public const PATTERN_SPACER = '{spacer}';
    public const SPACER = ' - ';

    private ?string $pattern = null;

    public function __construct(private SystemConfiguration $configuration)
    {
    }

    public function getChoicePattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->configuration->find('customer.choice_pattern');

            if ($this->pattern === null || stripos($this->pattern, '{') === false || stripos($this->pattern, '}') === false) {
                $this->pattern = self::PATTERN_NAME;
            }

            $this->pattern = str_replace(self::PATTERN_SPACER, self::SPACER, $this->pattern);
        }

        return $this->pattern;
    }

    public function getChoiceLabel(Customer $customer): string
    {
        $name = $this->getChoicePattern();
        $name = str_replace(self::PATTERN_NAME, $customer->getName(), $name);
        $name = str_replace(self::PATTERN_COMMENT, $customer->getComment() ?? '', $name);
        $name = str_replace(self::PATTERN_NUMBER, $customer->getNumber() ?? '', $name);
        $name = str_replace(self::PATTERN_COMPANY, $customer->getCompany() ?? '', $name);

        $name = ltrim($name, self::SPACER);
        $name = rtrim($name, self::SPACER);

        if ($name === '' || $name === self::SPACER) {
            $name = $customer->getName();
        }

        return substr($name, 0, 110);
    }
}
