<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

final class ProjectView
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $today;


    /**
     * @var integer
     */
    private $week;

    /**
     * @var integer
     */
    private $total;

    /**
     * @var integer
     */
    private $expectedDuration;

    /**
     * @var integer
     */
    private $expectedDelivery;

    /**
     * @var string
     */
    private $description;
}
