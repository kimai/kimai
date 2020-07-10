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
    public function __construct(RequestStack $requestStack, LanguageFormattings $formats)
    {
        $locale = Constants::DEFAULT_LOCALE;
        // request is null in a console command
        if (null !== $requestStack->getMasterRequest()) {
            $locale = $requestStack->getMasterRequest()->getLocale();
        }
        parent::__construct($formats, $locale);
    }
}
