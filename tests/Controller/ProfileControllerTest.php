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
use App\Tests\DataFixtures\TeamFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
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
        $this->assertHasProfileBox($client, 'John Doe');
        $this->assertHasAboutMeBox($client, UserFixtures::USERNAME_USER);
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $dates = [
            new \DateTime('-10 days'),
            new \DateTime('-1 year'),
        ];

        $em = $this->getEntityManager();

        foreach ($dates as $start) {
            $fixture = new TimesheetFixtures();
            $fixture->setAmount(10);
            $fixture->setUser($this->getUserByRole(User::ROLE_USER));
            $fixture->setStartDate($start);
            $this->importFixture($fixture);
        }

        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();

        foreach ($dates as $start) {
            $year = $start->format('Y');
            $this->assertStringContainsString('<h3 class="box-title">' . $year . '</h3>', $content);
            $this->assertStringContainsString('var userProfileChart' . $year . ' = new Chart(', $content);
        }

        $this->assertHasProfileBox($client, 'John Doe');
        $this->assertHasAboutMeBox($client, UserFixtures::USERNAME_USER);
    }

    protected function assertHasProfileBox(HttpKernelBrowser $client, string $username)
    {
        $profileBox = $client->getCrawler()->filter('div.box-body.box-profile');
        $this->assertEquals(1, $profileBox->count());
        $profileAvatar = $profileBox->filter('img.img-circle');
        $this->assertEquals(1, $profileAvatar->count());
        $alt = $profileAvatar->attr('alt');

        $this->assertEquals($username, $alt);
    }

    protected function assertHasAboutMeBox(HttpKernelBrowser $client, string $username)
    {
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('<h3 class="box-title">About me</h3>', $content);
        $this->assertStringContainsString('<td class="text-nowrap pull-right">' . $username . '</td>', $content);
    }

    public function getTabTestData()
    {
        $userTabs = ['#settings', '#password', '#api-token'];

        return [
            [User::ROLE_USER, UserFixtures::USERNAME_USER, ['#settings', '#password', '#api-token']],
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_SUPER_ADMIN, array_merge($userTabs, ['#teams', '#roles'])],
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
        $this->assertEquals(\count($expectedTabs), $tabs->count());
        $foundTabs = [];

        /** @var \DOMElement $tab */
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

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

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

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

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

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

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

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

        /** @var EncoderFactoryInterface $passwordEncoder */
        $passwordEncoder = static::$kernel->getContainer()->get('test.PasswordEncoder');

        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test123', $user->getSalt()));
        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());

        $form = $client->getCrawler()->filter('form[name=user_password]')->form();
        $client->submit($form, [
            'user_password' => [
                'plainPassword' => [
                    'first' => 'test1234',
                    'second' => 'test1234',
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/password'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test1234', $user->getSalt()));
    }

    public function testApiTokenAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/api-token');

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var EncoderFactoryInterface $passwordEncoder */
        $passwordEncoder = static::$kernel->getContainer()->get('test.PasswordEncoder');

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

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

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

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $form = $client->getCrawler()->filter('form[name=user_roles]')->form();
        $client->submit($form, [
            'user_roles[roles]' => [
                0 => 'ROLE_TEAMLEAD',
                2 => 'ROLE_SUPER_ADMIN',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/roles'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $this->assertEquals(['ROLE_TEAMLEAD', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testTeamsActionIsSecured()
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER . '/teams');
    }

    public function testTeamsActionIsSecuredForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/profile/' . UserFixtures::USERNAME_USER . '/teams');
    }

    public function testTeamsAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $em = $this->getEntityManager();

        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

        $fixture = new TeamFixtures();
        $fixture->setAmount(3);
        $fixture->setAddCustomer(true);
        $fixture->setAddUser(false);
        $fixture->addUserToIgnore($user);
        $this->importFixture($fixture);

        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/teams');

        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);
        $this->assertEquals([], $user->getTeams()->toArray());

        $form = $client->getCrawler()->filter('form[name=user_teams]')->form();
        /** @var ChoiceFormField $team */
        $team = $form->get('user_teams[teams][0]');
        $team->tick();

        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/teams'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $this->assertEquals(1, $user->getTeams()->count());
    }

    public function getPreferencesTestData()
    {
        return [
            // assert that the user doesn't have the "hourly-rate_own_profile" permission
            [User::ROLE_USER, UserFixtures::USERNAME_USER, 82, 82, 'ar', null],
            // admins are allowed to update their own hourly rate
            [User::ROLE_ADMIN, UserFixtures::USERNAME_ADMIN, 81, 37.5, 'ar', 19.54],
            // admins are allowed to update other peoples hourly rate
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_USER, 82, 37.5, 'en', 19.54],
        ];
    }

    /**
     * @dataProvider getPreferencesTestData
     */
    public function testPreferencesAction($role, $username, $hourlyRateOriginal, $hourlyRate, $expectedLocale, $expectedInternalRate)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, '/profile/' . $username . '/prefs');

        /** @var User $user */
        $user = $this->getUserByName($username);

        $this->assertEquals($hourlyRateOriginal, $user->getPreferenceValue(UserPreference::HOURLY_RATE));
        $this->assertNull($user->getPreferenceValue(UserPreference::INTERNAL_RATE));
        $this->assertNull($user->getPreferenceValue(UserPreference::SKIN));
        $this->assertEquals(false, $user->getPreferenceValue('theme.collapsed_sidebar'));
        $this->assertEquals('month', $user->getPreferenceValue('calendar.initial_view'));

        $form = $client->getCrawler()->filter('form[name=user_preferences_form]')->form();
        $client->submit($form, [
            'user_preferences_form' => [
                'preferences' => [
                    ['name' => UserPreference::HOURLY_RATE, 'value' => 37.5],
                    ['name' => UserPreference::INTERNAL_RATE, 'value' => 19.54],
                    ['name' => 'timezone', 'value' => 'America/Creston'],
                    ['name' => 'language', 'value' => 'ar'],
                    ['name' => UserPreference::SKIN, 'value' => 'blue'],
                    ['name' => 'theme.layout', 'value' => 'fixed'],
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

        $user = $this->getUserByName($username);

        $this->assertEquals($hourlyRate, $user->getPreferenceValue(UserPreference::HOURLY_RATE));
        $this->assertEquals($expectedInternalRate, $user->getPreferenceValue(UserPreference::INTERNAL_RATE));
        $this->assertEquals('', $user->getPreferenceValue('America/Creston'));
        $this->assertEquals('ar', $user->getPreferenceValue('language'));
        $this->assertEquals('blue', $user->getPreferenceValue(UserPreference::SKIN));
        $this->assertEquals(true, $user->getPreferenceValue('theme.collapsed_sidebar'));
        $this->assertEquals('agendaDay', $user->getPreferenceValue('calendar.initial_view'));
    }
}
