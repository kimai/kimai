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
use App\WorkingTime\Mode\WorkingTimeModeDay;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @group integration
 */
class ProfileControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER);
    }

    public function testMyProfileActionRedirects(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/');
        $this->assertIsRedirect($client, '/en/profile/' . UserFixtures::USERNAME_USER);
    }

    public function testIndexActionWithoutData(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasProfileBox($client, 'John Doe');
        $this->assertHasAboutMeBox($client, UserFixtures::USERNAME_USER);

        $content = $client->getResponse()->getContent();
        $year = (new \DateTime())->format('Y');
        self::assertStringContainsString('<h3 class="card-title">' . $year, $content);
        self::assertStringContainsString('new Chart(', $content);
        self::assertStringContainsString('<canvas id="userProfileChart' . $year . '"', $content);
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $dates = [
            new \DateTime('2018-06-13'),
            new \DateTime('2021-10-20'),
        ];

        foreach ($dates as $start) {
            $fixture = new TimesheetFixtures();
            $fixture->setAmount(10);
            $fixture->setUser($this->getUserByRole(User::ROLE_USER));
            $fixture->setStartDate($start);
            $this->importFixture($fixture);
        }

        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();

        foreach ($dates as $start) {
            $year = $start->format('Y');
            self::assertStringContainsString('<h3 class="card-title">' . $year, $content);
            self::assertStringContainsString('<canvas id="userProfileChart' . $year . '"', $content);
        }

        $this->assertHasProfileBox($client, 'John Doe');
        $this->assertHasAboutMeBox($client, UserFixtures::USERNAME_USER);
    }

    protected function assertHasProfileBox(HttpKernelBrowser $client, string $username): void
    {
        $profileBox = $client->getCrawler()->filter('div.box-user-profile');
        self::assertEquals(1, $profileBox->count());
        $profileAvatar = $profileBox->filter('span.avatar');
        self::assertEquals(1, $profileAvatar->count());
    }

    protected function assertHasAboutMeBox(HttpKernelBrowser $client, string $username): void
    {
        $content = $client->getResponse()->getContent();

        self::assertStringContainsString('<div class="datagrid-content">' . $username . '</div>', $content);
    }

    public static function getTabTestData(): array
    {
        return [
            [User::ROLE_USER, UserFixtures::USERNAME_USER],
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_SUPER_ADMIN],
        ];
    }

    /**
     * @dataProvider getTabTestData
     */
    public function testEditActionTabs($role, $username): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, '/profile/' . $username . '/edit');
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testIndexActionWithDifferentUsername(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/profile/' . UserFixtures::USERNAME_TEAMLEAD);
        self::assertFalse($client->getResponse()->isSuccessful());
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/edit');

        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertEquals(UserFixtures::USERNAME_USER, $user->getUserIdentifier());
        self::assertEquals('John Doe', $user->getAlias());
        self::assertEquals('Developer', $user->getTitle());
        self::assertEquals('john_user@example.com', $user->getEmail());
        self::assertTrue($user->isEnabled());

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        $client->submit($form, [
            'user_edit' => [
                'alias' => 'Johnny',
                'title' => 'Code Monkey',
                'email' => 'updated@example.com',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/edit'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertEquals(UserFixtures::USERNAME_USER, $user->getUserIdentifier());
        self::assertEquals('Johnny', $user->getAlias());
        self::assertEquals('Code Monkey', $user->getTitle());
        self::assertEquals('updated@example.com', $user->getEmail());
        self::assertTrue($user->isEnabled());
    }

    public function testEditActionWithActiveFlag(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/edit');

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        $client->submit($form, [
            'user_edit' => [
                'alias' => 'Johnny',
                'title' => 'Code Monkey',
                'email' => 'updated@example.com',
                'enabled' => false,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/edit'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertEquals(UserFixtures::USERNAME_USER, $user->getUserIdentifier());
        self::assertEquals('Johnny', $user->getAlias());
        self::assertEquals('Code Monkey', $user->getTitle());
        self::assertEquals('updated@example.com', $user->getEmail());
        self::assertFalse($user->isEnabled());
    }

    public function testPasswordAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/password');

        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

        /** @var PasswordHasherFactoryInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.password_hasher_factory');

        self::assertTrue($passwordEncoder->getPasswordHasher($user)->verify($user->getPassword(), UserFixtures::DEFAULT_PASSWORD));
        self::assertFalse($passwordEncoder->getPasswordHasher($user)->verify($user->getPassword(), 'test123'));
        self::assertEquals(UserFixtures::USERNAME_USER, $user->getUserIdentifier());

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
        // cannot follow redirect here, because the password was changed and the user/password registered in the client
        // are the old ones, so following the redirect would fail with "Unauthorized".

        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertFalse($passwordEncoder->getPasswordHasher($user)->verify($user->getPassword(), UserFixtures::DEFAULT_PASSWORD));
        self::assertTrue($passwordEncoder->getPasswordHasher($user)->verify($user->getPassword(), 'test1234'));
    }

    public function testPasswordActionFailsIfPasswordLengthToShort(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_USER,
            '/profile/' . UserFixtures::USERNAME_USER . '/password',
            'form[name=user_password]',
            [
                'user_password' => [
                    'plainPassword' => [
                        'first' => 'abcdef1',
                        'second' => 'abcdef1',
                    ]
                ]
            ],
            ['#user_password_plainPassword_first']
        );
    }

    /**
     * @group legacy
     */
    public function testApiTokenAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/api-token');

        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var PasswordHasherFactoryInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.password_hasher_factory');

        self::assertTrue($passwordEncoder->getPasswordHasher($user)->verify($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN));
        self::assertFalse($passwordEncoder->getPasswordHasher($user)->verify($user->getApiToken(), 'test1234'));
        self::assertEquals(UserFixtures::USERNAME_USER, $user->getUserIdentifier());

        $form = $client->getCrawler()->filter('form[name=user_api_password]')->form();
        $client->submit($form, [
            'user_api_password' => [
                'plainApiToken' => [
                    'first' => 'test1234',
                    'second' => 'test1234',
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/api-token'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertFalse($passwordEncoder->getPasswordHasher($user)->verify($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN));
        self::assertTrue($passwordEncoder->getPasswordHasher($user)->verify($user->getApiToken(), 'test1234'));
    }

    public function testApiTokenActionFailsIfPasswordLengthToShort(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_USER,
            '/profile/' . UserFixtures::USERNAME_USER . '/api-token',
            'form[name=user_api_password]',
            [
                'user_api_password' => [
                    'plainApiToken' => [
                        'first' => 'abcdef1',
                        'second' => 'abcdef1',
                    ]
                ]
            ],
            ['#user_api_password_plainApiToken_first']
        );
    }

    public function testRolesActionIsSecured(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');
        self::assertFalse($client->getResponse()->isSuccessful());
    }

    public function testRolesAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');

        /** @var User $user */
        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertEquals(['ROLE_USER'], $user->getRoles());

        $form = $client->getCrawler()->filter('form[name=user_roles]')->form();
        $client->submit($form, [
            'user_roles[roles]' => [
                0 => 'ROLE_TEAMLEAD',
                2 => 'ROLE_SUPER_ADMIN',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/roles'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testTeamsActionIsSecured(): void
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER . '/teams');
    }

    public function testTeamsActionIsSecuredForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/profile/' . UserFixtures::USERNAME_USER . '/teams');
    }

    public function testTeamsAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

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
        self::assertEquals([], $user->getTeams());

        $form = $client->getCrawler()->filter('form[name=user_teams]')->form();
        /** @var ChoiceFormField $team */
        $team = $form->get('user_teams[teams][0]');
        $team->tick();

        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/teams'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertCount(1, $user->getTeams());
    }

    public static function getPreferencesTestData(): array
    {
        return [
            // assert that the user doesn't have the "hourly-rate_own_profile" permission
            [User::ROLE_USER, UserFixtures::USERNAME_USER, 82, 82, 'ar', null, false],
            // teamleads are allowed to update their own hourly rate, but not other peoples hourly rate
            [User::ROLE_TEAMLEAD, UserFixtures::USERNAME_TEAMLEAD, 35, 37.5, 'ar', 19.54, true],
            // admins are allowed to update their own hourly rate, but not other peoples hourly rate
            [User::ROLE_ADMIN, UserFixtures::USERNAME_ADMIN, 81, 37.5, 'ar', 19.54, true],
            // super-admins are allowed to update other peoples hourly rate
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_ADMIN, 81, 37.5, 'en', 19.54, true],
            // super-admins are allowed to update their own hourly rate
            [User::ROLE_SUPER_ADMIN, UserFixtures::USERNAME_SUPER_ADMIN, 46, 37.5, 'ar', 19.54, true],
        ];
    }

    /**
     * @dataProvider getPreferencesTestData
     */
    public function testPreferencesAction($role, $username, $hourlyRateOriginal, $hourlyRate, string $expectedLocale, float|null $expectedInternalRate, bool $withRateSettings): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, '/profile/' . $username . '/prefs');

        /** @var User $user */
        $user = $this->getUserByName($username);

        self::assertEquals($hourlyRateOriginal, $user->getPreferenceValue(UserPreference::HOURLY_RATE));
        self::assertNull($user->getPreferenceValue(UserPreference::INTERNAL_RATE));
        self::assertEquals('default', $user->getPreferenceValue(UserPreference::SKIN));

        $data = [
            UserPreference::TIMEZONE => ['value' => 'America/Creston'],
            UserPreference::LANGUAGE => ['value' => 'ar'],
            UserPreference::LOCALE => ['value' => 'ru'],
            UserPreference::FIRST_WEEKDAY => ['value' => 'sunday'],
            UserPreference::SKIN => ['value' => 'dark'],
        ];

        if ($withRateSettings) {
            $data[UserPreference::HOURLY_RATE] = ['value' => 37.5];
            $data[UserPreference::INTERNAL_RATE] = ['value' => 19.54];
        }

        $form = $client->getCrawler()->filter('form[name=user_preferences_form]')->form();
        $client->submit($form, [
            'user_preferences_form' => [
                'preferences' => $data
            ]
        ]);

        $targetUrl = '/' . $expectedLocale . '/profile/' . urlencode($username) . '/prefs';

        $this->assertIsRedirect($client, $targetUrl);
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByName($username);

        self::assertEquals($hourlyRate, $user->getPreferenceValue(UserPreference::HOURLY_RATE));
        self::assertEquals($expectedInternalRate, $user->getPreferenceValue(UserPreference::INTERNAL_RATE));
        self::assertEquals('America/Creston', $user->getPreferenceValue(UserPreference::TIMEZONE));
        self::assertEquals('America/Creston', $user->getTimezone());
        self::assertEquals('ar', $user->getPreferenceValue(UserPreference::LANGUAGE));
        self::assertEquals('ru', $user->getPreferenceValue(UserPreference::LOCALE));
        self::assertEquals('ru', $user->getLocale());
        self::assertEquals('ar', $user->getLanguage());
        self::assertEquals('dark', $user->getPreferenceValue(UserPreference::SKIN));
        self::assertEquals('sunday', $user->getPreferenceValue(UserPreference::FIRST_WEEKDAY));
        self::assertEquals('sunday', $user->getFirstDayOfWeek());
    }

    public function testIsTwoFactorSecure(): void
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER . '/2fa');
    }

    public function testTwoFactor(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->getUserByName(UserFixtures::USERNAME_USER);
        self::assertFalse($user->hasTotpSecret());

        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/2fa');
        self::assertTrue($client->getResponse()->isSuccessful());

        $user = $this->getUserByName(UserFixtures::USERNAME_USER);
        self::assertTrue($user->hasTotpSecret());

        $formUrl = $this->createUrl('/profile/' . UserFixtures::USERNAME_USER . '/2fa');
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        self::assertStringContainsString(' data-toggle="tooltip" title="Click to show code" alt="TOTP QR Code" style="max-width: 200px; max-height: 200px;" src="', $content);
        self::assertStringContainsString('<form name="user_two_factor" method="post" action="' . $formUrl . '" id="user_two_factor_form">', $content);
    }

    public function testTwoFactorAsAdmin(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/profile/' . UserFixtures::USERNAME_USER . '/2fa');
    }

    public function testTwoFactorAsSuperAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/2fa');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $formUrl = $this->createUrl('/profile/' . UserFixtures::USERNAME_USER . '/2fa');

        self::assertStringContainsString(' data-toggle="tooltip" title="Click to show code" alt="TOTP QR Code" style="max-width: 200px; max-height: 200px;" src="', $content);
        self::assertStringContainsString('<form name="user_two_factor" method="post" action="' . $formUrl . '" id="user_two_factor_form">', $content);
    }

    public function testActivateTwoFactorWithEmptyToken(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_USER,
            '/profile/' . UserFixtures::USERNAME_USER . '/2fa',
            'form[name=user_two_factor]',
            [
                'user_two_factor' => [
                    'code' => ''
                ]
            ],
            ['#user_two_factor_code']
        );
    }

    public function testActivateTwoFactorWithWrongToken(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_USER,
            '/profile/' . UserFixtures::USERNAME_USER . '/2fa',
            'form[name=user_two_factor]',
            [
                'user_two_factor' => [
                    'code' => '1234567890oikjhb'
                ]
            ],
            ['#user_two_factor_code']
        );
    }

    public function testIsTwoFactorDeactivateSecure(): void
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER . '/2fa_deactivate', 'POST');
    }

    public function testContractActionIsSecured(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/contract');
        self::assertFalse($client->getResponse()->isSuccessful());
    }

    public function testContractAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/contract');
        self::assertTrue($client->getResponse()->isSuccessful());

        $user = $this->getUserByRole(User::ROLE_USER);
        $calculator = (new WorkingTimeModeDay())->getCalculator($user);

        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('monday this week')));
        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('tuesday this week')));
        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('wednesday this week')));
        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('thursday this week')));
        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('friday this week')));
        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('saturday this week')));
        self::assertEquals(0, $calculator->getWorkHoursForDay(new \DateTime('sunday this week')));

        $form = $client->getCrawler()->filter('form[name=user_contract]')->form();

        $client->submit($form, [
            'user_contract[workHoursMonday]' => '1:00',
            'user_contract[workHoursTuesday]' => '2:00',
            'user_contract[workHoursWednesday]' => '3:00',
            'user_contract[workHoursThursday]' => '4:30',
            'user_contract[workHoursFriday]' => '5:12',
            'user_contract[workHoursSaturday]' => '6:59',
            'user_contract[workHoursSunday]' => '0:01',
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/contract'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $this->getUserByRole(User::ROLE_USER);
        $calculator = (new WorkingTimeModeDay())->getCalculator($user);

        self::assertEquals(3600, $calculator->getWorkHoursForDay(new \DateTime('monday this week')));
        self::assertEquals(7200, $calculator->getWorkHoursForDay(new \DateTime('tuesday this week')));
        self::assertEquals(10800, $calculator->getWorkHoursForDay(new \DateTime('wednesday this week')));
        self::assertEquals(16200, $calculator->getWorkHoursForDay(new \DateTime('thursday this week')));
        self::assertEquals(18720, $calculator->getWorkHoursForDay(new \DateTime('friday this week')));
        self::assertEquals(25140, $calculator->getWorkHoursForDay(new \DateTime('saturday this week')));
        self::assertEquals(60, $calculator->getWorkHoursForDay(new \DateTime('sunday this week')));
    }
}
