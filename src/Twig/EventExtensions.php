<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Twig\Runtime\ThemeEventExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EventExtensions extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('trigger', [ThemeEventExtension::class, 'trigger']),
        ];
    }
}
