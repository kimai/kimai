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
            ->setName('login_initial_view')
            ->setValue('my_profile')
            ->setType(InitialViewType::class);

        $em->persist($pref);
        $user->addPreference($pref);

        $user->setLanguage('ar');

        $em->flush();

        $this->request($client, '/homepage');
        $this->assertIsRedirect($client, '/ar/profile/');
    }
}
