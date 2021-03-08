<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Duration extends Regex
{
    public function __construct($options = null)
    {
        $patterns = [
            // decimal times (can be separated by comma or dot, depending on the locale)
            '[0-9]{1,}',
            '[0-9]{1,}[,.]{1}[0-9]{1,}',
            // ASP.NET style time spans - https://momentjs.com/docs/#/durations/
            '[0-9]{1,}:[0-9]{1,}:[0-9]{1,}',
            '[0-9]{1,}:[0-9]{1,}',
            // https://en.wikipedia.org/wiki/ISO_8601#Time_intervals
            '[0-9]{1,}[hHmMsS]{1}',
            '[0-9]{1,}[hH]{1}[0-9]{1,}[mM]{1}',
            '[0-9]{1,}[hHmM]{1}[0-9]{1,}[sS]{1}',
            '[0-9]{1,}[mM]{1}[0-9]{1,}[sS]{1}',
            '[0-9]{1,}[hH]{1}[0-9]{1,}[mM]{1}[0-9]{1,}[sS]{1}',
        ];
        $options['pattern'] = '/^' . implode('$|^', $patterns) . '$/';

        parent::__construct($options);
    }
}
