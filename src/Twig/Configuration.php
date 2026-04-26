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
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityError;
use Twig\TwigFunction;

final class Configuration extends AbstractExtension
{
    public function __construct(private readonly SystemConfiguration $configuration)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', $this->get(...), ['needs_environment' => true]),
        ];
    }

    public function get(Environment $environment, string $name)
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
            case 'themeAllowAvatarUrls':
                return $this->configuration->isThemeAllowAvatarUrls();
                // whitelisted configs that can be read even in invoice environments
            case 'theme.branding.logo':
            case 'theme.branding.company':
                return $this->configuration->find($name);
        }

        if (str_starts_with($name, 'saml.') || str_starts_with($name, 'ldap.')) {
            throw new SecurityError(\sprintf('Templates cannot access security configuration %s.', $name));
        }

        if ($environment->hasExtension(SandboxExtension::class)) {
            $sandbox = $environment->getExtension(SandboxExtension::class);
            if ($sandbox->isSandboxed()) {
                throw new SecurityError('Sandboxed template tried to access configuration key: ' . $name);
            }
        }

        $checks = ['is' . $name, 'get' . $name, 'has' . $name, $name];

        foreach ($checks as $methodName) {
            if (method_exists($this->configuration, $methodName)) {
                return \call_user_func($this->configuration->$methodName(...));
            }
        }

        return $this->configuration->find($name);
    }

    public function __call($name, $arguments)
    {
        @trigger_error('Accessing "kimai_config" is deprecated and always return null, use config() instead', E_USER_DEPRECATED);

        return null;
    }
}
