<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\User;
use App\Utils\AvatarService;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AvatarExtension extends AbstractExtension
{
    /**
     * @var AvatarService
     */
    private $avatar;
    /**
     * @var Packages
     */
    private $packages;

    public function __construct(AvatarService $avatar, Packages $packages)
    {
        $this->avatar = $avatar;
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('avatar', [$this, 'getAvatarUrl']),
        ];
    }

    public function getAvatarUrl(?User $profile, string $default): string
    {
        if (null === $profile) {
            return $this->packages->getUrl($default);
        }

        $url = $this->avatar->getAvatar($profile);

        if (null === $url) {
            return $this->packages->getUrl($default);
        }

        return $this->packages->getUrl($url);
    }
}
