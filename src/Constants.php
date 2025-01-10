<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

/**
 * Some "very" global constants for Kimai.
 */
final class Constants
{
    /**
     * The current release version
     */
    public const VERSION = '2.28.0';
    /**
     * The current release: major * 10000 + minor * 100 + patch
     */
    public const VERSION_ID = 22800;
    /**
     * The software name
     */
    public const SOFTWARE = 'Kimai';
    /**
     * Used in multiple views
     */
    public const GITHUB = 'https://github.com/kimai/kimai/';
    /**
     * The GitHub repository name
     */
    public const GITHUB_REPO = 'kimai/kimai';
    /**
     * Homepage, used in multiple views
     */
    public const HOMEPAGE = 'https://www.kimai.org';
    /**
     * Default color for Customer, Project and Activity entities
     */
    public const DEFAULT_COLOR = '#d2d6de';
}
