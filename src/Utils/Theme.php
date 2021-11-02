<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Configuration\SystemConfiguration;
use App\Constants;
use App\Entity\User;

final class Theme
{
    private $configuration;
    /**
     * @var bool|null
     */
    private $randomColors;

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getUserColor(User $user): string
    {
        $color = $user->getColor();

        if ($color !== null) {
            return $color;
        }

        $identifier = $user->getDisplayName();
        if ($this->randomColors === null) {
            $this->randomColors = $this->configuration->isThemeRandomColors();
        }

        if ($this->randomColors) {
            return (new Color())->getRandom($identifier);
        }

        return Constants::DEFAULT_COLOR;
    }

    public function getColor(?string $color, ?string $identifier = null): string
    {
        if ($color !== null) {
            return $color;
        }

        if ($this->randomColors === null) {
            $this->randomColors = $this->configuration->isThemeRandomColors();
        }

        if ($this->randomColors) {
            return (new Color())->getRandom($identifier);
        }

        return Constants::DEFAULT_COLOR;
    }
}
