<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * The default markdown implementation.
 */
final class ParsedownExtension extends Parsedown
{
    /**
     * Overwritten to prevent # and = to show up as headings for two reasons:
     * - Hashes are often used to cross-link issues in other systems
     * - Headings should not occur in time record listings
     */
    protected $BlockTypes = [
        '*' => ['Rule', 'List'],
        '+' => ['List'],
        '-' => ['Table', 'Rule', 'List'],
        '0' => ['List'],
        '1' => ['List'],
        '2' => ['List'],
        '3' => ['List'],
        '4' => ['List'],
        '5' => ['List'],
        '6' => ['List'],
        '7' => ['List'],
        '8' => ['List'],
        '9' => ['List'],
        ':' => ['Table'],
        '<' => ['Comment', 'Markup'],
        '>' => ['Quote'],
        '[' => ['Reference'],
        '_' => ['Rule'],
        '`' => ['FencedCode'],
        '|' => ['Table'],
        '~' => ['FencedCode'],
    ];
}
