<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use App\Entity\MetaTableTypeInterface;
use App\Entity\User;
use App\Pdf\PdfContext;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\String\UnicodeString;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * The Twig environment needs the sandbox extension, which itself needs a policy to start working.
 */
final class StrictPolicy implements SecurityPolicyInterface
{
    /** @var string[] */
    private array $allowedTags = ['block', 'if', 'for', 'set', 'macro', 'import', 'extends', 'from'];
    /** @var string[] */
    private array $allowedFunctions = [
        // vendor/twig/twig/src/Extension/CoreExtension.php
        'max', 'min', 'range', 'constant', 'cycle', 'random', 'date',
        // vendor/symfony/twig-bridge/Extension/TranslationExtension.php
        't',
        // vendor/symfony/webpack-encore-bundle/src/Twig/EntryFilesTwigExtension.php
        'encore_entry_css_source', 'encore_entry_link_tags', 'encore_entry_script_tags',
        // vendor/symfony/twig-bridge/Extension/SecurityExtension.php
        'is_granted',
        // Twig/RuntimeExtensions.php
        'qr_code_data_uri',
        // Twig/Configuration.php
        'config',
        // Twig/LocaleFormatExtensions.php
        'create_date', 'month_names', 'locale_format',
        // Twig/Extensions.php
        'class_name'
    ];
    /** @var string[] */
    private array $allowedFilters = [
        // vendor/twig/twig/src/Extension/CoreExtension.php
        // formatting filters
        'date', 'date_modify', 'format', 'replace', 'number_format', 'abs', 'round',
        // encoding
        'url_encode', 'json_encode',
        // string filters
        'title', 'capitalize', 'upper', 'lower', 'striptags', 'trim', 'nl2br', 'spaceless',
        // array helpers
        'join', 'split', 'sort', 'merge', 'column', 'filter', 'map',
        // string/array filters
        'reverse', 'length', 'slice', 'first', 'last',
        // iteration and runtime
        'default', 'keys',
        // vendor/twig/twig/src/Extension/EscaperExtension.php
        'escape', 'e', 'raw',
        // vendor/symfony/twig-bridge/Extension/TranslationExtension.php
        'trans',
        // vendor/twig/string-extra/StringExtension.php
        'u', 'slug',
        // vendor/twig/intl-extra/IntlExtension.php
        'country_name', 'currency_name', 'currency_symbol', 'language_name', 'locale_name', 'timezone_name',
        'format_currency', 'format_number', 'format_decimal_number', 'format_currency_number',
        'format_duration_number', 'format_datetime', 'format_date', 'format_time',
        // src/Twig/LocaleFormatExtensions.php
        'month_name', 'day_name', 'date_short', 'date_time', 'date_full', 'date_format',
        'date_weekday', 'time', 'duration', 'duration_decimal', 'money', 'amount',
        // src/Twig/RuntimeExtensions.php
        'md2html', 'desc2html', 'comment2html', 'comment1line',
        // src/Twig/Extensions.php
        'multiline_indent', 'color', 'nl2str'
    ];

    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (!\in_array($tag, $this->allowedTags, true)) {
                throw new SecurityNotAllowedTagError(\sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!\in_array($filter, $this->allowedFilters, true)) {
                throw new SecurityNotAllowedFilterError(\sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (!\in_array($function, $this->allowedFunctions, true)) {
                throw new SecurityNotAllowedFunctionError(\sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof UnicodeString) {
            return;
        }

        if ($obj instanceof ServerBag) {
            throw new SecurityNotAllowedMethodError('Tried to access server environment', ServerBag::class, $method);
        }

        if ($obj instanceof SessionInterface) {
            throw new SecurityNotAllowedMethodError('Tried to access session', SessionInterface::class, $method);
        }

        $lcm = strtolower($method);

        if ($obj instanceof PdfContext) {
            if ($lcm !== 'setoption') {
                throw new SecurityNotAllowedMethodError('Tried to access forbidden method on PdfContext', PdfContext::class, $method);
            }

            return;
        }

        if ($obj instanceof MetaTableTypeInterface && $lcm === 'merge') {
            return;
        }

        if ($obj instanceof Request) {
            if (!str_starts_with($lcm, 'get')) {
                throw new SecurityNotAllowedMethodError('Tried to call setter() of app variable', AppVariable::class, $method);
            }

            return;
        }

        if ($obj instanceof AppVariable) {
            if (!\in_array($lcm, ['getrequest', 'getuser', 'getlocale'], true)) {
                throw new SecurityNotAllowedMethodError('Tried to access forbidden app variable method', User::class, $method);
            }

            return;
        }

        if (!str_starts_with($lcm, 'get') && !str_starts_with($lcm, 'has') && !str_starts_with($lcm, 'is') && $lcm !== '__tostring') {
            throw new SecurityNotAllowedMethodError('Tried to access non-read method', $obj::class, $method);
        }

        if ($obj instanceof User) {
            if (\in_array($lcm, ['getpassword', 'gettotpsecret', 'getplainpassword', 'getconfirmationtoken', 'gettotpauthenticationconfiguration'], true)) {
                throw new SecurityNotAllowedMethodError('Tried to access user secrets', User::class, $method);
            }
        }
    }

    public function checkPropertyAllowed($obj, $property): void
    {
    }
}
