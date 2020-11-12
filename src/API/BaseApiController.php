<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\User;
use App\Timesheet\DateTimeFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * @method null|User getUser()
 */
abstract class BaseApiController extends AbstractController
{
    public const DATE_FORMAT = DateTimeType::HTML5_FORMAT;
    public const DATE_FORMAT_PHP = 'Y-m-d\TH:i:s';

    protected function getDateTimeFactory(?User $user = null): DateTimeFactory
    {
        if (null === $user) {
            $user = $this->getUser();
        }

        return new DateTimeFactory(new \DateTimeZone($user->getTimezone()));
    }
}
