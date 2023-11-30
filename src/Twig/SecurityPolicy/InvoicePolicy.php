<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use App\Invoice\InvoiceModel;
use App\Pdf\PdfContext;
use Symfony\Component\String\UnicodeString;
use Twig\Markup;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Template;

/**
 * Represents the security policy for custom Twig invoice templates.
 */
final class InvoicePolicy implements SecurityPolicyInterface
{
    private ChainPolicy $policy;

    public function __construct()
    {
        $this->policy = new ChainPolicy();
        $this->policy->addPolicy(new DefaultPolicy());
        $this->policy->addPolicy(new SecurityPolicy(
            ['block', 'if', 'for', 'set', 'extends', 'import'],
            [
                // =================================================================
                // vendor/twig/twig/src/Extension/CoreExtension.php

                // formatting filters
                'date',
                'date_modify',
                'format',
                'replace',
                'number_format',
                'abs',
                'round',

                // encoding
                'url_encode',
                'json_encode',
                'convert_encoding',

                // string filters
                'title',
                'capitalize',
                'upper',
                'lower',
                'striptags',
                'trim',
                'nl2br',
                'spaceless',

                // array helpers
                'join',
                'split',
                'sort',
                'merge',
                'batch',
                'column',
                'filter',
                'map',
                'reduce',

                // string/array filters
                'reverse',
                'length',
                'slice',
                'first',
                'last',

                // iteration and runtime
                'default',
                'keys',

                // =================================================================
                // vendor/twig/twig/src/Extension/EscaperExtension.php
                'escape',
                'e',
                'raw',

                // =================================================================
                // vendor/symfony/twig-bridge/Extension/TranslationExtension.php
                'trans',

                // =================================================================
                // vendor/twig/string-extra/StringExtension.php
                'u',
                'slug',

                // =================================================================
                // vendor/twig/intl-extra/IntlExtension.php
                'country_name',
                'currency_name',
                'currency_symbol',
                'language_name',
                'locale_name',
                'timezone_name',
                'format_currency',
                'format_number',
                'format_decimal_number',
                'format_currency_number',
                'format_percent_number',
                'format_scientific_number',
                'format_spellout_number',
                'format_ordinal_number',
                'format_duration_number',
                'format_datetime',
                'format_date',
                'format_time',

                // =================================================================
                // src/Twig/LocaleFormatExtensions.php
                'month_name',
                'day_name',
                'date_short',
                'date_time',
                'date_full',
                'date_format',
                'date_weekday',
                'time',
                'duration',
                'duration_decimal',
                'money',
                'amount',

                // =================================================================
                // src/Twig/RuntimeExtensions.php
                'md2html',
                'desc2html',
                'comment2html',
                'comment1line',

                // =================================================================
                // src/Twig/Extensions.php
                'multiline_indent',
                'color',
                'font_contrast',
                'default_color',
                'nl2str',
            ],
            [
                PdfContext::class => ['setoption'],
                InvoiceModel::class => ['toarray'],
            ],
            [], // properties
            [
                // =================================================================
                // vendor/twig/twig/src/Extension/CoreExtension.php
                'max',
                'min',
                'range',
                'constant',
                'cycle',
                'random',
                'date',
                'asset',
                'range',

                // =================================================================
                // vendor/symfony/twig-bridge/Extension/TranslationExtension.php
                't',

                // =================================================================
                // vendor/symfony/webpack-encore-bundle/src/Twig/EntryFilesTwigExtension.php
                'encore_entry_css_source',

                // =================================================================
                // vendor/symfony/twig-bridge/Extension/AssetExtension.php
                'asset',

                // =================================================================
                // vendor/symfony/twig-bridge/Extension/SecurityExtension.php
                'is_granted',

                // =================================================================
                // Twig/RuntimeExtensions.php
                'qr_code_data_uri',

                // =================================================================
                // Twig/Configuration.php
                'config',

                // =================================================================
                // Twig/LocaleFormatExtensions.php
                'create_date',
                'month_names',
                'locale_format',
            ]
        ));
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->policy->checkSecurity($tags, $filters, $functions);
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof Template || $obj instanceof Markup || $obj instanceof UnicodeString) {
            return;
        }

        $lm = strtolower($method);

        if (str_starts_with($lm, 'get') || str_starts_with($lm, 'is') || str_starts_with($lm, 'has')) {
            return;
        }

        if ($lm === '__tostring') {
            return;
        }

        $this->policy->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->policy->checkPropertyAllowed($obj, $property);
    }
}
