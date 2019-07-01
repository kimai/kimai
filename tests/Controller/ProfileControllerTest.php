<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Tests\DataFixtures\TimesheetFixtures;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * @group integration
 */
class ProfileControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER);
    }

    public function testIndexActionWithoutData()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasNoEntriesWithFilter($client);
        $this->assertHasProfileBox($client, UserFixtures::USERNAME_USER);
        $this->assertHasAboutMeBox($client, UserFixtures::USERNAME_USER);
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $dates = [
            new \DateTime('-10 days'),
            new \DateTime('-1 year'),
        ];

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        foreach ($dates as $start) {
            $fixture = new TimesheetFixtures();
            $fixture->setAmount(10);
            $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
            $fixture->setStartDate($start);
            $this->importFixture($em, $fixture);
        }

        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();

        foreach ($dates as $start) {
            $year = $start->format('Y');
            $this->assertContains('<h3 class="box-title">' . $year . '</h3>', $content);
            $this->assertContains('var userProfileChart' . $year . ' = new Chart(', $content);
        }

        $this->assertHasProfileBox($client, UserFixtures::USERNAME_USER);
        $this->assertHasAboutMeBox($client, UserFixtures::USERNAME_USER);
    }

    protected function assertHasProfileBox(Client $client, string $username)
    {
        $profileBox = $client->getCrawler()->filter('div.box-body.box-profile');
        $this->assertEquals(1, $profileBox->count());
        $profileAvatar = $profileBox->filter('img.profile-user-img');
        $this->assertEquals(1, $profileAvatar->count());
        $alt = $profileAvatar->attr('alt');

        $this->assertEquals($username, $alt);
    }

    protected function assertHasAboutMeBox(Client $client, string $username)
    {
        $content = $client->getResponse()->getContent();

        $this->assertContains('<h3 class="box-title">About me</h3>', $content);
        $this->assertContains('<span class="pull-right badge bg-blue">' . $username . '</span>', $content);
    }

    public function getTabTestData()
    {
        $userTabs = ['#settings', '#password', '#api-token'];

        return [
            [User::ROLE_USER, UserFixtures::USERNAME_USER, ['#settings', '#password', '#api-token']],
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_SUPER_ADMIN, array_merge($userTabs, ['#roles'])],
        ];
    }

    /**
     * @dataProvider getTabTestData
     */
    public function testEditActionTabs($role, $username, $expectedTabs)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, '/profile/' . $username . '/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $tabs = $client->getCrawler()->filter('div.nav-tabs-custom ul.nav-tabs li');
        $this->assertEquals(count($expectedTabs), $tabs->count());
        $foundTabs = [];
        foreach ($tabs->filter('a') as $tab) {
            $foundTabs[] = $tab->getAttribute('href');
        }
        $this->assertEmpty(array_diff($expectedTabs, $foundTabs));
    }

    public function testIndexActionWithDifferentUsername()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/profile/' . UserFixtures::USERNAME_TEAMLEAD);
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/edit');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());
        $this->assertEquals('John Doe', $user->getAlias());
        $this->assertEquals('Developer', $user->getTitle());
        $this->assertEquals(UserFixtures::DEFAULT_AVATAR, $user->getAvatar());
        $this->assertEquals('john_user@example.com', $user->getEmail());
        $this->assertTrue($user->isEnabled());

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        $client->submit($form, [
            'user_edit' => [
                'alias' => 'Johnny',
                'title' => 'Code Monkey',
                'avatar' => '/fake/image.jpg',
                'email' => 'updated@example.com',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/edit'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());
        $this->assertEquals('Johnny', $user->getAlias());
        $this->assertEquals('Code Monkey', $user->getTitle());
        $this->assertEquals('/fake/image.jpg', $user->getAvatar());
        $this->assertEquals('updated@example.com', $user->getEmail());
        $this->assertTrue($user->isEnabled());
    }

    public function testEditActionWithActiveFlag()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/edit');

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        $client->submit($form, [
            'user_edit' => [
                'alias' => 'Johnny',
                'title' => 'Code Monkey',
                'avatar' => '/fake/image.jpg',
                'email' => 'updated@example.com',
                'enabled' => false,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/edit'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());
        $this->assertEquals('Johnny', $user->getAlias());
        $this->assertEquals('Code Monkey', $user->getTitle());
        $this->assertEquals('/fake/image.jpg', $user->getAvatar());
        $this->assertEquals('updated@example.com', $user->getEmail());
        $this->assertFalse($user->isEnabled());
    }

    public function testPasswordAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/password');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        /** @var EncoderFactoryInterface $passwordEncoder */
        $passwordEncoder = $client->getContainer()->get('test.PasswordEncoder');

        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test123', $user->getSalt()));
        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());

        $form = $client->getCrawler()->filter('form[name=user_password]')->form();
        $client->submit($form, [
            'user_password' => [
                'plainPassword' => [
                    'first' => 'test123',
                    'second' => 'test123',
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/password'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test123', $user->getSalt()));
    }

    public function testApiTokenAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/api-token');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);
        /** @var EncoderFactoryInterface $passwordEncoder */
        $passwordEncoder = $client->getContainer()->get('test.PasswordEncoder');

        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN, $user->getSalt()));
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), 'test123', $user->getSalt()));
        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());

        $form = $client->getCrawler()->filter('form[name=user_api_token]')->form();
        $client->submit($form, [
            'user_api_token' => [
                'plainApiToken' => [
                    'first' => 'test123',
                    'second' => 'test123',
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/api-token'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), 'test123', $user->getSalt()));
    }

    public function testRolesActionIsSecured()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testRolesAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $form = $client->getCrawler()->filter('form[name=user_roles]')->form();
        $client->submit($form, [
            'user_roles[roles]' => [
                'ROLE_TEAMLEAD',
                'ROLE_SUPER_ADMIN',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/roles'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(['ROLE_TEAMLEAD', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function getPreferencesTestData()
    {
        return [
            // assert that the user doesn't have the "hourly-rate_own_profile" permission
            [User::ROLE_USER, UserFixtures::USERNAME_USER, 82, 82, 'ar'],
            // admins are allowed to update their own hourly rate
            [User::ROLE_ADMIN, UserFixtures::USERNAME_ADMIN, 81, 37.5, 'ar'],
            // admins are allowed to update other peoples hourly rate
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_USER, 82, 37.5, 'en'],
        ];
    }

    /**
     * @dataProvider getPreferencesTestData
     */
    public function testPreferencesAction($role, $username, $hourlyRateOriginal, $hourlyRate, $expectedLocale)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, '/profile/' . $username . '/prefs');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByName($em, $username);

        $this->assertEquals($hourlyRateOriginal, $user->getPreferenceValue(UserPreference::HOURLY_RATE));
        $this->assertNull($user->getPreferenceValue(UserPreference::SKIN));
        $this->assertEquals(false, $user->getPreferenceValue('theme.collapsed_sidebar'));
        $this->assertEquals('month', $user->getPreferenceValue('calendar.initial_view'));

        $form = $client->getCrawler()->filter('form[name=user_preferences_form]')->form();
        $client->submit($form, [
            'user_preferences_form' => [
                'preferences' => [
                    ['name' => UserPreference::HOURLY_RATE, 'value' => 37.5],
                    ['name' => 'timezone', 'value' => 'America/Creston'],
                    ['name' => 'language', 'value' => 'ar'],
                    ['name' => UserPreference::SKIN, 'value' => 'blue'],
                    ['name' => 'theme.collapsed_sidebar', 'value' => true],
                    ['name' => 'calendar.initial_view', 'value' => 'agendaDay'],
                ]
            ]
        ]);

        $targetUrl = '/' . $expectedLocale . '/profile/' . urlencode($username) . '/prefs';

        $this->assertIsRedirect($client, $targetUrl);
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByName($em, $username);

        $this->assertEquals($hourlyRate, $user->getPreferenceValue(UserPreference::HOURLY_RATE));
        $this->assertEquals('', $user->getPreferenceValue('America/Creston'));
        $this->assertEquals('ar', $user->getPreferenceValue('language'));
        $this->assertEquals('blue', $user->getPreferenceValue(UserPreference::SKIN));
        $this->assertEquals(true, $user->getPreferenceValue('theme.collapsed_sidebar'));
        $this->assertEquals('agendaDay', $user->getPreferenceValue('calendar.initial_view'));
    }
}
