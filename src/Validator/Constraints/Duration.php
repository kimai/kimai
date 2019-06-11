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
            '[0-9]{1,}',
            '[0-9]{1,}:[0-9]{1,}:[0-9]{1,}',
            '[0-9]{1,}:[0-9]{1,}',
            '[0-9]{1,}[hmsHMS]{1}',
            '[0-9]{1,}[hmsHMS]{1}[0-9]{1,}[hmsHMS]{1}',
            '[0-9]{1,}[hmsHMS]{1}[0-9]{1,}[hmsHMS]{1}[0-9]{1,}[hmsHMS]{1}',
        ];
        $options['pattern'] = '/^' . implode('$|^', $patterns) . '$/';

        parent::__construct($options);
    }
}
