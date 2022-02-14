<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Configuration\LanguageFormattings;
use App\Constants;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Use this class, when you want information about formats for the "current request locale".
 */
final class LocaleSettings extends LocaleFormats
{
    private $requestStack;
    private $locale;

    public function __construct(RequestStack $requestStack, LanguageFormattings $formats)
    {
        parent::__construct($formats, Constants::DEFAULT_LOCALE);
        $this->requestStack = $requestStack;
    }

    public function getLocale(): string
    {
        if ($this->locale === null) {
            $locale = \Locale::getDefault();

            // request is null in a console command
            if (null !== $this->requestStack->getMasterRequest()) {
                $locale = $this->requestStack->getMasterRequest()->getLocale();
            }
            $this->locale = $locale;
        }

        return $this->locale;
    }
}
