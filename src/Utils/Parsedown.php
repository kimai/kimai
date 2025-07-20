<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * This Class extends the default Parsedown Class for custom methods.
 */
class Parsedown extends \Parsedown
{
    protected function blockTable($Line, array $Block = null) // @phpstan-ignore missingType.return,missingType.iterableValue,missingType.parameter
    {
        $Block = parent::blockTable($Line, $Block);

        if ($Block === null) {
            return null;
        }

        $Block['element']['attributes']['class'] = 'table table-striped table-vcenter';

        return $Block;
    }
}
