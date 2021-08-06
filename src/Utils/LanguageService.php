<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Constants;

final class LanguageService
{
    /**
     * @var string[]|string
     */
    private $locales;

    public function __construct(string $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @return string[]
     */
    public function getAllLanguages(): array
    {
        if (!\is_array($this->locales)) {
            // no further checks, because the list of languages is hard coded and we can be sure that
            // it is well formatted and contains the default langauge english
            $this->locales = array_unique(explode('|', trim($this->locales)));
        }

        return $this->locales;
    }

    public function isKnownLanguage(string $language): bool
    {
        return \in_array($language, $this->getAllLanguages());
    }

    public function getDefaultLanguage(): string
    {
        return Constants::DEFAULT_LOCALE;
    }
}
