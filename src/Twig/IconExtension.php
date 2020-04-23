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
        'about' => 'fas fa-info-circle',
        'activity' => 'fas fa-tasks',
        'admin' => 'fas fa-wrench',
        'audit' => 'fas fa-history',
        'avatar' => 'fas fa-user',
        'back' => 'fas fa-long-arrow-alt-left',
        'calendar' => 'far fa-calendar-alt',
        'clock' => 'far fa-clock',
        'configuration' => 'fas fa-cogs',
        'copy' => 'far fa-copy',
        'create' => 'far fa-plus-square',
        'csv' => 'fas fa-table',
        'customer' => 'fas fa-user-tie',
        'dashboard' => 'fas fa-tachometer-alt',
        'debug' => 'far fa-file-alt',
        'delete' => 'far fa-trash-alt',
        'doctor' => 'fas fa-medkit',
        'dot' => 'fas fa-circle',
        'download' => 'fas fa-download',
        'duration' => 'far fa-hourglass',
        'edit' => 'far fa-edit',
        'end' => 'fas fa-stopwatch',
        'export' => 'fas fa-file-export',
        'fax' => 'fas fa-fax',
        'filter' => 'fas fa-filter',
        'help' => 'far fa-question-circle',
        'home' => 'fas fa-home',
        'invoice' => 'fas fa-file-invoice-dollar',
        'invoice-template' => 'fas fa-file-signature',
        'left' => 'fas fa-chevron-left',
        'list' => 'fas fa-list',
        'locked' => 'fas fa-lock',
        'logout' => 'fas fa-sign-out-alt',
        'mail' => 'fas fa-envelope-open',
        'mail-sent' => 'fas fa-paper-plane',
        'manual' => 'fas fa-book',
        'mobile' => 'fas fa-mobile',
        'money' => 'far fa-money-bill-alt',
        'ods' => 'fas fa-table',
        'off' => 'fas fa-toggle-off',
        'on' => 'fas fa-toggle-on',
        'pin' => 'fas fa-thumbtack',
        'pdf' => 'fas fa-file-pdf',
        'pause' => 'fas fa-pause',
        'pause-small' => 'far fa-pause-circle',
        'permissions' => 'fas fa-user-lock',
        'phone' => 'fas fa-phone',
        'plugin' => 'fas fa-plug',
        'print' => 'fas fa-print',
        'profile' => 'fas fa-user-edit',
        'profile-stats' => 'far fa-chart-bar',
        'project' => 'fas fa-briefcase',
        'repeat' => 'fas fa-redo-alt',
        'right' => 'fas fa-chevron-right',
        'roles' => 'fas fa-user-shield',
        'search' => 'fas fa-search',
        'settings' => 'fas fa-cog',
        'shop' => 'fas fa-shopping-cart',
        'start' => 'fas fa-play',
        'start-small' => 'far fa-play-circle',
        'stop' => 'fas fa-stop',
        'stop-small' => 'far fa-stop-circle',
        'success' => 'fas fa-check',
        'tag' => 'fas fa-tags',
        'team' => 'fas fa-users',
        'timesheet' => 'fas fa-clock',
        'timesheet-team' => 'fas fa-user-clock',
        'trash' => 'far fa-trash-alt',
        'unlocked' => 'fas fa-unlock-alt',
        'upload' => 'fas fa-upload',
        'user' => 'fas fa-user-friends',
        'visibility' => 'far fa-eye',
        'warning' => 'fas fa-exclamation-triangle',
        'xlsx' => 'fas fa-file-excel',
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
