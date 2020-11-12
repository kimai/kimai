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
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class Version
{
    /**
     * Kimai Version, eg. "1.9"
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $version = Constants::VERSION;
    /**
     * Candidate: either "prod" or "dev"
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $candidate = Constants::STATUS;
    /**
     * Full version including status, eg: "1.9-prod"
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $semver = Constants::VERSION . '-' . Constants::STATUS;
    /**
     * The version name
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $name = Constants::NAME;
    /**
     * A full copyright notice
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $copyright = Constants::SOFTWARE . ' - ' . Constants::VERSION . ' ' . Constants::STATUS . ' (' . Constants::NAME . ') by Kevin Papst and contributors.';
}
