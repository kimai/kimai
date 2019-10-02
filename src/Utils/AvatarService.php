<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Entity\User;
use Laravolt\Avatar\Avatar;
use Laravolt\Avatar\Generator\DefaultGenerator;

class AvatarService
{
    /**
     * @var string
     */
    private $projectDirectory;

    public const AVATAR_CONFIG = [
        'driver' => 'gd',
        'generator' => DefaultGenerator::class,
        'ascii' => true,
        'shape' => 'circle',
        'width' => 100,
        'height' => 100,
        'chars' => 2,
        'fontSize' => 44,
        'fontFamily' => null,
        'uppercase' => true,
        //'fonts' => ['path/to/OpenSans-Bold.ttf', 'path/to/rockwell.ttf'],
        'foregrounds' => [
            '#FFFFFF'
        ],
        'backgrounds' => [
            '#f44336',
            '#E91E63',
            '#9C27B0',
            '#673AB7',
            '#3F51B5',
            '#2196F3',
            '#03A9F4',
            '#00BCD4',
            '#009688',
            '#4CAF50',
            '#8BC34A',
            '#CDDC39',
            '#FFC107',
            '#FF9800',
            '#FF5722',
        ],
        'border' => [
            'size' => 1,
            'color' => 'background'
        ],
        'theme' => '*',
        'themes' => [
            'grayscale-light' => [
                'backgrounds' => ['#edf2f7', '#e2e8f0', '#cbd5e0'],
                'foregrounds' => ['#a0aec0'],
            ],
            'grayscale-dark' => [
                'backgrounds' => ['#2d3748', '#4a5568', '#718096'],
                'foregrounds' => ['#e2e8f0'],
            ],
            'colorful' => [
                'backgrounds' => [
                    '#f44336',
                    '#E91E63',
                    '#9C27B0',
                    '#673AB7',
                    '#3F51B5',
                    '#2196F3',
                    '#03A9F4',
                    '#00BCD4',
                    '#009688',
                    '#4CAF50',
                    '#8BC34A',
                    '#CDDC39',
                    '#FFC107',
                    '#FF9800',
                    '#FF5722',
                ],
                'foregrounds' => ['#FFFFFF'],
            ],
        ]
    ];

    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    private function getAvatarUrl(User $profile): string
    {
        return '/avatars/' . md5($profile->getId() . '_' . $profile->getDisplayName()) . '.png';
    }

    private function getImagePath(User $profile): string
    {
        $avatarPath = realpath($this->projectDirectory . '/public/');

        return $avatarPath . $this->getAvatarUrl($profile);
    }

    public function generateAvatar(User $profile, bool $regenerate = false): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }

        $filePath = $this->getImagePath($profile);

        if ($regenerate || !file_exists($filePath)) {
            if (!is_writable(dirname($filePath))) {
                // FIXME add logging
                return false;
            }
            $avatar = new Avatar(self::AVATAR_CONFIG);
            $avatar->create($profile->getDisplayName())->save($filePath, 90);
            $avatar->create($profile->getDisplayName())->save($filePath, 90);
        }

        return true;
    }

    public function getAvatar(User $profile): ?string
    {
        if (!empty(trim($profile->getAvatar()))) {
            return $profile->getAvatar();
        }

        if (!$this->generateAvatar($profile)) {
            return null;
        }

        return $this->getAvatarUrl($profile);
    }
}
