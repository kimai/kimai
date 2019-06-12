<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractContainer;
use App\Widget\Type\CompoundRow;

/**
 * @covers \App\Widget\Type\CompoundRow
 * @covers \App\Widget\Type\AbstractContainer
 */
class CompoundRowTest extends AbstractContainerTest
{
    public function createSut(): AbstractContainer
    {
        return new CompoundRow();
    }
}
