<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Symfony\Bridge\Twig\Extension\AssetExtension as BaseAssetExtension;

class AssetExtension extends BaseAssetExtension
{
    /**
     * Overwritten to support subdirectories and subdomains at the same time.
     *
     * @param string $path
     * @param null $packageName
     * @return mixed|string
     */
    public function getAssetUrl($path, $packageName = null)
    {
        return str_replace('./', 'build/', parent::getAssetUrl($path, $packageName));
    }
}
