<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Form\Type\InitialViewType;
use App\Form\Type\LanguageType;

/**
 * @group integration
 */
class HomepageControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/homepage');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/homepage');
        $this->assertIsRedirect($client, '/en/timesheet/');
    }

    public function testIndexActionWithChangedPreferences()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $pref = (new UserPreference())
            ->setName('login.initial_view')
            ->setValue('my_profile')
            ->setType(InitialViewType::class);

        $user->addPreference($pref);

        $pref = (new UserPreference())
            ->setName('language')
            ->setValue('ar')
            ->setType(LanguageType::class);

        $user->addPreference($pref);

        $em->persist($pref);

        $this->request($client, '/homepage');
        $this->assertIsRedirect($client, '/ar/profile/');
    }
}
