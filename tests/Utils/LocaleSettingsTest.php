<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Configuration\LanguageFormattings;
use App\Utils\LocaleSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \App\Utils\LocaleSettings
 * @covers \App\Configuration\LanguageFormattings
 */
class LocaleSettingsTest extends LocaleFormatsTest
{
    protected function getSut(string $locale, array $settings)
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new LocaleSettings($requestStack, new LanguageFormattings($settings));
    }
}
