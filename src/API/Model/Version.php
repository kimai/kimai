<?php

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
     * Kimai Version, eg. "2.0.0"
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     * @phpstan-ignore-next-line
     */
    private string $version = Constants::VERSION;
    /**
     * Kimai Version as integer, eg. 20000
     *
     * Follows the same logic as PHP_VERSION_ID, see https://www.php.net/manual/de/function.phpversion.php
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="integer")
     * @phpstan-ignore-next-line
     */
    private int $versionId = Constants::VERSION_ID;
    /**
     * Full version including status, eg: "2.0.0-stable"
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     * @phpstan-ignore-next-line
     */
    private string $semver = Constants::VERSION . '-stable';
    /**
     * The version name
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     * @phpstan-ignore-next-line
     */
    private string $name = Constants::NAME;
    /**
     * A full copyright notice
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     * @phpstan-ignore-next-line
     */
    private string $copyright = Constants::SOFTWARE . ' ' . Constants::VERSION . ' by Kevin Papst.';
}
