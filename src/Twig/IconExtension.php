<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class IconExtension extends AbstractExtension
{
    /**
     * @var string[]
     */
    private static $icons = [];

    public function __construct()
    {
        self::load();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('icon', [$this, 'icon']),
        ];
    }

    private static function load()
    {
        if (empty(self::$icons)) {
            self::$icons = include \dirname(\dirname(__DIR__)) . '/config/icons.php';
        }
    }

    /**
     * Returns the icon class by its alias.
     *
     * @param string $name name of the icon alias
     * @param string $default the class to use (or default if not found)
     * @return string
     */
    public function icon(string $name, string $default = ''): string
    {
        return self::$icons[$name] ?? $default;
    }

    /**
     * Allows to register new named icons.
     * Once registered icons cannot be
     *
     * @param string $name
     * @param string $icon
     */
    public static function registerIcon(string $name, string $icon)
    {
        self::load();

        if (!\array_key_exists($name, self::$icons)) {
            self::$icons[$name] = $icon;
        }
    }
}
