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
class Constants
{
    /**
     * The current release version
     */
    public const VERSION = '1.16.2';
    /**
     * The current release: major * 10000 + minor * 100 + patch
     */
    public const VERSION_ID = 11602;
    /**
     * The current release status, either "stable" or "dev"
     */
    public const STATUS = 'prod';
    /**
     * The software name
     */
    public const SOFTWARE = 'Kimai';
    /**
     * The release name, will only change for new major version
     */
    public const NAME = 'Ayumi';
    /**
     * Used in multiple views
     */
    public const GITHUB = 'https://github.com/kevinpapst/kimai2/';
    /**
     * Homepage, used in multiple views
     */
    public const HOMEPAGE = 'https://www.kimai.org';
    /**
     * Application wide default locale
     */
    public const DEFAULT_LOCALE = 'en';
    /**
     * Default color for Customer, Project and Activity entities
     */
    public const DEFAULT_COLOR = '#d2d6de';
}
