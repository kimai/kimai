<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

interface PluginInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getPath();
}
