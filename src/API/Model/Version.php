<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use App\Constants;

class Version
{
    /**
     * @var string
     */
    protected $version = Constants::VERSION;
    /**
     * @var string
     */
    protected $candidate = Constants::STATUS;
    /**
     * @var string
     */
    protected $semver = Constants::VERSION . '-' . Constants::STATUS;
    /**
     * @var string
     */
    protected $name = Constants::NAME;
    /**
     * @var string
     */
    protected $copyright = Constants::SOFTWARE . ' - ' . Constants::VERSION . ' ' . Constants::STATUS . ' (' . Constants::NAME . ') by Kevin Papst and contributors.';
}
