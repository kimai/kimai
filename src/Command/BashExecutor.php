<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

class BashExecutor
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->rootDir = realpath($projectDirectory);
    }

    /**
     * @param string $command
     * @return BashResult
     */
    public function execute(string $command)
    {
        $exitCode = 0;

        $command = rtrim($this->rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($command, DIRECTORY_SEPARATOR);

        passthru($command, $exitCode);

        return new BashResult($exitCode);
    }
}
