<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\BashExecutor;
use App\Command\BashResult;

class TestBashExecutor extends BashExecutor
{
    /**
     * @var BashResult
     */
    protected $result;
    /**
     * @var string
     */
    protected $command;

    /**
     * @param BashResult $result
     * @return $this
     */
    public function setResult(BashResult $result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     * @return BashResult
     */
    public function execute(string $command)
    {
        $this->command = $command;

        return $this->result;
    }
}
