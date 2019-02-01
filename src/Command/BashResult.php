<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

class BashResult
{
    /**
     * @var string
     */
    protected $exitCode;
    /**
     * @var string
     */
    protected $result;

    /**
     * @param string $exitCode
     * @param string $result
     */
    public function __construct($exitCode, $result)
    {
        $this->exitCode = $exitCode;
        $this->result = $result;
    }

    /**
     * @return string
     */
    public function getExitCode(): string
    {
        return $this->exitCode;
    }

    /**
     * @param string $exitCode
     * @return BashResult
     */
    public function setExitCode(string $exitCode)
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * @param string $result
     * @return BashResult
     */
    public function setResult(string $result)
    {
        $this->result = $result;

        return $this;
    }
}
