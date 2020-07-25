<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Profiler;

use App\Twig\IconExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

final class IconCollector extends DataCollector
{
    public function collect(Request $request, Response $response)
    {
        $extension = new IconExtension();
        $property = new \ReflectionProperty($extension, 'icons');
        $property->setAccessible(true);
        $icons = $property->getValue();

        $this->data = [
            'amount' => \count($icons),
            'icons' => $icons,
        ];
    }

    public function getName()
    {
        return 'kimai.icon-collector';
    }

    public function reset()
    {
        $this->data = [];
    }

    public function getAmount()
    {
        return $this->data['amount'];
    }

    public function getIcons()
    {
        return $this->data['icons'];
    }
}
