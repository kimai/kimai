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

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return Version
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getCandidate(): string
    {
        return $this->candidate;
    }

    /**
     * @param string $candidate
     * @return Version
     */
    public function setCandidate(string $candidate)
    {
        $this->candidate = $candidate;
        return $this;
    }

    /**
     * @return string
     */
    public function getSemver(): string
    {
        return $this->semver;
    }

    /**
     * @param string $semver
     * @return Version
     */
    public function setSemver(string $semver)
    {
        $this->semver = $semver;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Version
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getCopyright(): string
    {
        return $this->copyright;
    }

    /**
     * @param string $copyright
     * @return Version
     */
    public function setCopyright(string $copyright)
    {
        $this->copyright = $copyright;
        return $this;
    }
}
