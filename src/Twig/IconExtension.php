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
    private static $icons = [
        'activity' => 'fas fa-tasks',
        'admin' => 'fas fa-wrench',
        'calendar' => 'far fa-calendar-alt',
        'customer' => 'fas fa-user-tie',
        'copy' => 'far fa-copy',
        'create' => 'far fa-plus-square',
        'dashboard' => 'fas fa-tachometer-alt',
        'delete' => 'far fa-trash-alt',
        'download' => 'fas fa-download',
        'duration' => 'far fa-hourglass',
        'edit' => 'far fa-edit',
        'filter' => 'fas fa-filter',
        'help' => 'far fa-question-circle',
        'invoice' => 'fas fa-file-invoice',
        'list' => 'fas fa-list',
        'logout' => 'fas fa-sign-out-alt',
        'manual' => 'fas fa-book',
        'money' => 'far fa-money-bill-alt',
        'print' => 'fas fa-print',
        'project' => 'fas fa-briefcase',
        'repeat' => 'fas fa-redo-alt',
        'start' => 'fas fa-play-circle',
        'start-small' => 'far fa-play-circle',
        'stop' => 'fas fa-stop',
        'stop-small' => 'far fa-stop-circle',
        'timesheet' => 'fas fa-clock',
        'trash' => 'far fa-trash-alt',
        'user' => 'fas fa-users',
        'visibility' => 'far fa-eye',
        'settings' => 'fas fa-cog',
        'export' => 'fas fa-file-export',
        'pdf' => 'fas fa-file-pdf',
        'csv' => 'fas fa-table',
        'ods' => 'fas fa-table',
        'xlsx' => 'fas fa-file-excel',
        'on' => 'fas fa-toggle-on',
        'off' => 'fas fa-toggle-off',
        'audit' => 'fas fa-history',
        'home' => 'fas fa-home',
        'shop' => 'fas fa-shopping-cart',
        'about' => 'fas fa-info-circle',
        'debug' => 'far fa-file-alt',
        'profile-stats' => 'far fa-chart-bar',
        'profile' => 'fas fa-user-edit',
        'warning' => 'fas fa-exclamation-triangle',
        'permissions' => 'fas fa-user-lock',
        'back' => 'fas fa-long-arrow-alt-left',
        'tag' => 'fas fa-tags',
        'avatar' => 'fas fa-user',
        'timesheet-team' => 'fas fa-user-clock',
        'plugin' => 'fas fa-plug',
        'configuration' => 'fas fa-cogs',
    ];

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('icon', [$this, 'icon']),
        ];
    }

    public function icon(string $name, string $default = ''): string
    {
        return self::$icons[$name] ?? $default;
    }
}
