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

#[Serializer\ExclusionPolicy('all')]
class Version
{
    /**
     * Kimai Version, eg. "2.0.0"
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $version = Constants::VERSION;
    /**
     * Kimai Version as integer, eg. 20000
     *
     * Follows the same logic as PHP_VERSION_ID, see https://www.php.net/manual/de/function.phpversion.php
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    private int $versionId = Constants::VERSION_ID;
    /**
     * Full version including status, eg: "2.0.0-stable"
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $semver = Constants::VERSION . '-stable';
    /**
     * The version name
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $name = Constants::NAME;
    /**
     * A full copyright notice
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $copyright = Constants::SOFTWARE . ' ' . Constants::VERSION . ' by Kevin Papst.';
}
