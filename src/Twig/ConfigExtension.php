<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\ThemeConfiguration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    /**
     * @var ThemeConfiguration
     */
    protected $configuration;

    public function __construct(ThemeConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('theme_config', [$this, 'getThemeConfig']),
        ];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getThemeConfig(string $name)
    {
        return $this->configuration->find($name);
    }
}
