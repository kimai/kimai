<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Security;

use App\Entity\User;
use App\Tests\Mocks\AbstractMockFactory;
use App\Timesheet\UserDateTimeFactory;

class UserDateTimeFactoryFactory extends AbstractMockFactory
{
    public function create(?string $timezone = null): UserDateTimeFactory
    {
        $userFactory = new CurrentUserFactory($this->getTestCase());
        $currentUser = $userFactory->create(new User(), $timezone);

        return new UserDateTimeFactory($currentUser);
    }
}
