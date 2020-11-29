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
 * Overwritten base console application to display application name and version.
 */
class ConsoleApplication extends Application
{
    public function getName()
    {
        return Constants::SOFTWARE;
    }

    public function getVersion()
    {
        if (Constants::STATUS !== 'stable') {
            return sprintf('%s (%s)', Constants::VERSION, Constants::STATUS);
        }

        return Constants::VERSION;
    }
}
