<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\TimesheetConfiguration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TimesheetConfigExtension extends AbstractExtension
{
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    public function __construct(TimesheetConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_duration_only', [$this, 'isDurationOnly']),
        ];
    }

    public function isDurationOnly(): bool
    {
        return $this->configuration->isDurationOnly();
    }
}
