<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\SystemConfiguration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ConfigExtension extends AbstractExtension
{
    /**
     * @var SystemConfiguration
     */
    private $configuration;

    public function __construct(SystemConfiguration $configuration)
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
        switch ($name) {
            case 'auto_reload_datatable':
                @trigger_error('The configuration auto_reload_datatable is deprecated and was removed with 1.4', E_USER_DEPRECATED);

                return false;

            case 'soft_limit':
                return $this->configuration->getTimesheetActiveEntriesSoftLimit();

            default:
                $name = 'theme.' . $name;
                break;
        }

        return $this->configuration->find($name);
    }
}
