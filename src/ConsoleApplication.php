<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @internal
 */
final class ConsoleApplication extends Application
{
    public function getName(): string
    {
        return Constants::SOFTWARE;
    }

    public function getVersion(): string
    {
        return Constants::VERSION;
    }

    /**
     * Overwritten to prevent unwanted SF core messages to show up here.
     *
     * @return string
     */
    public function getLongVersion(): string
    {
        return \sprintf('%s <info>%s</info> (env: <comment>%s</>, debug: <comment>%s</>)', $this->getName(), $this->getVersion(), $this->getKernel()->getEnvironment(), $this->getKernel()->isDebug() ? 'true' : 'false');
    }
}
