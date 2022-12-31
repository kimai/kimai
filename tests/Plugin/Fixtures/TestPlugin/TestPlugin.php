<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Plugin\Fixtures\TestPlugin;

use App\Plugin\PluginInterface;

class TestPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'TestPlugin';
    }

    public function getPath(): string
    {
        return __DIR__;
    }
}
