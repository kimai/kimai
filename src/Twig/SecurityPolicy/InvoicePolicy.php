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
            ['block', 'if', 'for', 'set', 'extends'],
            [
                // Twig core filters
                'map', 'escape', 'trans', 'default', 'nl2br', 'trim', 'raw',
                'join', 'u', 'slice', 'date', 'month_name', 'first', 'country_name',
                'replace', 'length', 'number_format', 'split',

                // Kimai filters
                'md2html', 'desc2html', 'comment2html', 'comment1line', 'multiline_indent', 'nl2str',
                'date_short', 'duration', 'amount', 'money', 'duration_decimal',
            ],
            [
                PdfContext::class => ['setoption'],
                InvoiceModel::class => ['toarray'],
            ],
            [], // properties
            [
                // Twig core functions
                'cycle', 'asset', 'range',

                // Kimai functions
                'encore_entry_css_source', 'qr_code_data_uri', 'config',
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
