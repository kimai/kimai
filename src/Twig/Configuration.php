<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\SystemConfiguration;
use App\Constants;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Configuration extends AbstractExtension
{
    public function __construct(private SystemConfiguration $configuration)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', [$this, 'get']),
        ];
    }

    public function get(string $name)
    {
        switch ($name) {
            case 'chart-class':
                return ''; // 'chart';
            case 'theme.chart.background_color':
                return '#3c8dbc';
            case 'theme.chart.border_color':
                return '#3b8bba';
            case 'theme.chart.grid_color':
                return 'rgba(0,0,0,.05)';
            case 'theme.chart.height':
                return '300';
            case 'theme.calendar.background_color':
                return Constants::DEFAULT_COLOR;
        }

        return $this->configuration->find($name);
    }

    public function __call($name, $arguments)
    {
        $checks = ['is' . $name, 'get' . $name, 'has' . $name, $name];

        foreach ($checks as $methodName) {
            if (method_exists($this->configuration, $methodName)) {
                return \call_user_func([$this->configuration, $methodName], $arguments);
            }
        }

        return $this->configuration->find($name);
    }
}
