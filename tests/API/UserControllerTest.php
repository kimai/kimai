<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\Role;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\Mocks\PrepareUserEventSubscriberMock;
use App\Timesheet\RateServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class UserControllerTest extends APIControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/users');
    }

    /**
     * @return array<array<string>>
     */
    public static function getRoleTestData(): array
    {
        return [
            [User::ROLE_USER],
            [User::ROLE_TEAMLEAD],
            [User::ROLE_ADMIN],
        ];
    }

    #[DataProvider('getRoleTestData')]
    public function testIsSecureForRole(string $role): void
    {
        $this->assertUrlIsSecuredForRole($role, '/api/users');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(7, \count($result));
        foreach ($result as $user) {
            self::assertIsArray($user);
            self::assertApiResponseTypeStructure('UserCollection', $user);
        }
    }

    public function testGetCollectionFull(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users?full=true');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(7, \count($result));
        foreach ($result as $user) {
            self::assertIsArray($user);
            self::assertApiResponseTypeStructure('UserEntity', $user);
        }
    }

    public function testGetCollectionWithQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users', 'GET', ['visible' => 2, 'orderBy' => 'email', 'order' => 'DESC', 'term' => 'chris']);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(1, \count($result));
        foreach ($result as $user) {
            self::assertIsArray($user);
            self::assertApiResponseTypeStructure('UserCollection', $user);
        }
    }

    public function testGetCollectionWithQuery2(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users', 'GET', ['visible' => 3, 'orderBy' => 'email', 'order' => 'DESC']);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(8, \count($result));
        foreach ($result as $user) {
            self::assertIsArray($user);
            self::assertApiResponseTypeStructure('UserCollection', $user);
        }
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/1');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        self::assertEquals('1', $result['id']);
        self::assertEquals('CFO', $result['title']);
        self::assertEquals('Clara Haynes', $result['alias']);
    }

    public function testGetMyProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/me');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        self::assertEquals('6', $result['id']);
        self::assertEquals('Super Administrator', $result['title']);
        self::assertEquals('', $result['alias']);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_SUPER_ADMIN, '/api/users/99');
    }

    public function testGetEntityAccessDenied(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertApiAccessDenied($client, '/api/users/4', 'You are not allowed to view this profile');
    }

    public function testGetEntityAccessAllowedForOwnProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/users/2');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'title' => 'asdfghjkl',
            'plainPassword' => 'foo@example.com',
            'enabled' => true,
            'supervisor' => 2,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], (string) json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['username']);
        self::assertEquals('asdfghjkl', $result['title']);
        self::assertTrue($result['enabled']);
        self::assertEquals('ru', $result['language']);
        self::assertEquals('Europe/Paris', $result['timezone']);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_ADMIN'], $result['roles']);
    }

    public function testPostActionWithShortPassword(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'title' => 'asdfghjkl',
            'plainPassword' => '1234567',
            'enabled' => true,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['plainPassword']);
    }

    public function testPostActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => '',
            'email' => '',
            'plainPassword' => '123456',
            'language' => 'xx',
            'timezone' => 'XXX/YYY',
            'roles' => [
                'ABC',
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['username', 'email', 'plainPassword', 'language', 'timezone', 'roles']);
    }

    public function testPostActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'plainPassword' => 'foo@example.com',
            'enabled' => true,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
        ];
        $this->request($client, '/api/users', 'POST', [], (string) json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'Access denied.');
    }

    public function testPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'title' => 'asdfghjkl',
            'plainPassword' => 'foo@example.com',
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], (string) json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);
        self::assertIsArray($result);
        self::assertFalse($result['enabled']);

        $data = [
            'title' => 'qwertzui',
            'enabled' => true,
            'language' => 'it',
            'timezone' => 'America/New_York',
            'roles' => [
                'ROLE_TEAMLEAD',
            ],
        ];
        $id = $result['id'];
        self::assertIsNumeric($id);
        $this->request($client, '/api/users/' . $id, 'PATCH', [], (string) json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['username']);
        self::assertEquals('qwertzui', $result['title']);
        self::assertTrue($result['enabled']);
        self::assertEquals('it', $result['language']);
        self::assertEquals('America/New_York', $result['timezone']);
        self::assertEquals(['ROLE_TEAMLEAD'], $result['roles']);
    }

    public function testPatchActionCannotChangeSystemAccountAndPasswordResetOnOwnProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setRequiresPasswordReset(true);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
        self::assertTrue($this->loadUserFromDatabase(UserFixtures::USERNAME_USER)->requiresPasswordReset());

        $data = [
            'title' => 'qwertzui',
            'systemAccount' => true,
            'requiresPasswordReset' => false,
        ];
        $this->request($client, '/api/users/' . $user->getId(), 'PATCH', [], (string) json_encode($data));
        $this->assertApiCallValidationError($client->getResponse(), [], true, [], [], ['systemAccount', 'requiresPasswordReset']);

        $this->getEntityManager()->clear();
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        self::assertFalse($user->isSystemAccount());
        self::assertTrue($user->requiresPasswordReset());
        self::assertEquals('Developer', $user->getTitle());
    }

    public function testPatchActionCannotChangeSystemAccountAndPasswordResetAsTeamlead(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $user = $this->getUserByRole(User::ROLE_USER);

        $data = [
            'systemAccount' => true,
            'requiresPasswordReset' => true,
        ];
        $this->request($client, '/api/users/' . $user->getId(), 'PATCH', [], (string) json_encode($data));
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Access denied.');

        $this->getEntityManager()->clear();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertFalse($user->isSystemAccount());
        self::assertFalse($user->requiresPasswordReset());
    }

    public function testPatchActionSystemAccountAndPasswordResetAsSuperAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $user = $this->getUserByRole(User::ROLE_USER);

        $data = [
            'systemAccount' => true,
            'requiresPasswordReset' => true,
        ];
        $this->request($client, '/api/users/' . $user->getId(), 'PATCH', [], (string) json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->getEntityManager()->clear();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertTrue($user->isSystemAccount());
        self::assertTrue($user->requiresPasswordReset());
    }

    public function testPatchActionWithUnknownUser(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_SUPER_ADMIN, '/api/users/255', []);
    }

    public function testPatchActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/api/users/1', 'PATCH', [], (string) json_encode(['language' => 'hu']));
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Not allowed to edit user');
    }

    public function testPatchActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => '1',  // not existing in form
            'email' => '',
            'plainPassword' => '123456', // not existing in form
            'plainApiToken' => '123456', // not existing in form
            'language' => 'xx',
            'timezone' => 'XXX/YYY',
            'roles' => [
                'ABC',
            ],
        ];
        $this->request($client, '/api/users/1', 'PATCH', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['email', 'language', 'timezone', 'roles'], true);
    }

    // ------------------------------------- [USER PREFERENCES] -------------------------------------

    public function testUpdateUserPreferenceThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/users/42/preferences', []);
    }

    public function testUpdateUserPreferenceThrowsExceptionOnWrongStructure(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_SUPER_ADMIN, '/api/users/1/preferences', ['name' => 'X', 'value' => 'X'], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testUpdateUserPreferenceThrowsExceptionOnMissingName(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_SUPER_ADMIN, '/api/users/1/preferences', [['value' => 'X']], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testUpdateUserPreferenceThrowsExceptionOnMissingValue(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_SUPER_ADMIN, '/api/users/1/preferences', [['name' => 'X']], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testUpdateUserPreferenceThrowsExceptionOnMissingMetafield(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_SUPER_ADMIN, '/api/users/1/preferences', [['name' => 'X', 'value' => 'Y']], [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }

    public function testUpdateUserPreference(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new PrepareUserEventSubscriberMock());

        $data = [
            [
                'name' => 'metatestmock',
                'value' => 'another,testing,bar'
            ]
        ];
        $this->request($client, '/api/users/1/preferences', 'PATCH', [], (string) json_encode($data));

        self::assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find(1);
        self::assertEquals('another,testing,bar', $user->getPreferenceValue('metatestmock'));
        self::assertEquals('another,testing,bar', $user->getPreferenceValue('metatestmock'));
    }

    public function testUpdateUserPreferenceWithEnabledPreference(): void
    {
        $role = User::ROLE_SUPER_ADMIN;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals(46, $user->getPreferenceValue('hourly_rate'));

        $data = [
            [
                'name' => 'hourly_rate',
                'value' => 99
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals(99, $user->getPreferenceValue('hourly_rate'));
    }

    public function testUpdateUserPreferenceValidatesConfiguredConstraints(): void
    {
        $role = User::ROLE_SUPER_ADMIN;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $data = [
            [
                'name' => 'hourly_rate',
                'value' => -5
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['hourly_rate']);

        $em = $this->getEntityManager();
        $em->clear();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals(46, $user->getPreferenceValue('hourly_rate'));
    }

    public function testUpdateUserPreferenceValidatesConstraintsOfRegisteredPreference(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new PrepareUserEventSubscriberMock());

        $data = [
            [
                'name' => 'metatestmock',
                'value' => str_repeat('x', 201)
            ]
        ];
        $this->request($client, '/api/users/1/preferences', 'PATCH', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['metatestmock']);
    }

    public function testUpdateUserPreferenceStoresNothingIfOneValueIsInvalid(): void
    {
        $role = User::ROLE_SUPER_ADMIN;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $data = [
            [
                'name' => 'hourly_rate',
                'value' => 99
            ],
            [
                'name' => 'internal_rate',
                'value' => -5
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['internal_rate']);

        $em = $this->getEntityManager();
        $em->clear();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals(46, $user->getPreferenceValue('hourly_rate'));
    }

    /**
     * @return array<int, array{string|null}>
     */
    public static function getClearedPreferenceValues(): array
    {
        return [
            [null],
            [''],
        ];
    }

    #[DataProvider('getClearedPreferenceValues')]
    public function testUpdateUserPreferenceStoresClearedValueAsNull(?string $cleared): void
    {
        $role = User::ROLE_SUPER_ADMIN;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);
        $em = $this->getEntityManager();

        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode([['name' => 'internal_rate', 'value' => 50]]));
        self::assertTrue($client->getResponse()->isSuccessful());

        $em->clear();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals(50, $user->getPreferenceValue('internal_rate'));

        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode([['name' => 'internal_rate', 'value' => $cleared]]));
        self::assertTrue($client->getResponse()->isSuccessful());

        $em->clear();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertNull($user->getPreferenceValue('internal_rate'));

        // the rate calculation falls back to the hourly rate, if the internal rate is not configured
        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setBegin(new \DateTime('2025-01-01 10:00:00'));
        $timesheet->setEnd(new \DateTime('2025-01-01 11:00:00'));
        $timesheet->setDuration(3600);
        /** @var RateServiceInterface $rateService */
        $rateService = static::getContainer()->get(RateServiceInterface::class);
        self::assertEquals(46, $rateService->calculate($timesheet)->getInternalRate());
    }

    /**
     * @return array<int, array{string, string|int|null}>
     */
    public static function getInvalidPreferenceValues(): array
    {
        return [
            ['timezone', 'XXX/YYY'],
            ['timezone', null],
            ['language', 'xx'],
            ['language', null],
            ['locale', 'xx'],
            ['locale', null],
            ['first_weekday', 'friday'],
            ['first_weekday', null],
            ['skin', 'foo'],
            ['skin', null],
            ['calendar_initial_view', 'year'],
            ['calendar_initial_view', null],
            ['login_initial_view', str_repeat('x', 151)],
            ['login_initial_view', null],
            ['work_contract_type', 'foo'],
            ['work_contract_type', null],
            ['work_monday', -1],
            ['work_sunday', 86401],
            ['work_monday', 'foo'],
            ['holidays', -1],
            ['public_holiday_group', str_repeat('x', 251)],
            ['work_start_day', 'foo'],
            ['work_last_day', '2026-13-01'],
        ];
    }

    #[DataProvider('getInvalidPreferenceValues')]
    public function testUpdateUserPreferenceRejectsInvalidValue(string $name, string|int|null $value): void
    {
        $role = User::ROLE_SUPER_ADMIN;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $data = [
            [
                'name' => $name,
                'value' => $value
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, [$name]);
    }

    /**
     * @return array<int, array{string, string|int}>
     */
    public static function getValidPreferenceValues(): array
    {
        return [
            ['timezone', 'Europe/Berlin'],
            ['language', 'de'],
            ['locale', 'de_CH'],
            ['first_weekday', 'sunday'],
            ['skin', 'dark'],
            ['calendar_initial_view', 'week'],
            ['login_initial_view', 'timesheet'],
            ['work_contract_type', 'day'],
            ['work_monday', 28800],
            ['work_sunday', 86400],
            ['holidays', '30.5'],
            ['public_holiday_group', '1'],
            ['work_start_day', '2026-01-01'],
            ['work_last_day', '2026-12-31'],
        ];
    }

    #[DataProvider('getValidPreferenceValues')]
    public function testUpdateUserPreferenceAcceptsValidValue(string $name, string|int $value): void
    {
        $role = User::ROLE_SUPER_ADMIN;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $data = [
            [
                'name' => $name,
                'value' => $value
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));

        self::assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        $em->clear();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals((string) $value, $user->getPreferenceValue($name));
    }

    public function testUpdateUserPreferenceWithDisabledPreference(): void
    {
        $role = User::ROLE_USER;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        self::assertEquals(82, $user->getPreferenceValue('hourly_rate'));

        $data = [
            [
                'name' => 'hourly_rate',
                'value' => 99
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));
        self::assertApiResponseAccessDenied($client->getResponse());
    }

    /**
     * Regression test: internal preferences (prefixed with "__") must never be writable through the API,
     * otherwise a user could clear the password reset flag that was set by an administrator.
     */
    public function testUpdateUserPreferenceCannotChangeInternalPreference(): void
    {
        $role = User::ROLE_USER;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $em = $this->getEntityManager();
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        self::assertSame($id, $user->getId());
        $user->setRequiresPasswordReset(true);
        $em->persist($user);
        $em->flush();
        $em->clear();

        $data = [
            [
                'name' => '__pw_reset__',
                'value' => '0'
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));
        self::assertApiResponseAccessDenied($client->getResponse());

        $em->clear();
        self::assertTrue($this->loadUserFromDatabase(UserFixtures::USERNAME_USER)->requiresPasswordReset());
    }

    public function testUpdateUserPreferenceCannotChangeInternalPreferenceWithDots(): void
    {
        $role = User::ROLE_USER;
        $id = $this->getAuthenticatedUserId($role);
        $client = $this->getClientForAuthenticatedUser($role);

        $em = $this->getEntityManager();
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        self::assertSame($id, $user->getId());
        $user->setRequiresPasswordReset(true);
        $em->persist($user);
        $em->flush();
        $em->clear();

        // names are normalized, dots are replaced by underscores
        $data = [
            [
                'name' => '..pw_reset__',
                'value' => '0'
            ]
        ];
        $this->request($client, '/api/users/' . $id . '/preferences', 'PATCH', [], (string) json_encode($data));
        self::assertApiResponseAccessDenied($client->getResponse());

        $em->clear();
        self::assertTrue($this->loadUserFromDatabase(UserFixtures::USERNAME_USER)->requiresPasswordReset());
    }

    public function testDeleteRoleIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/users/roles/1', 'DELETE');
    }

    public function testDeleteRoleIsSecureForRole(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $role = new Role();
        $role->setName('TEST_ROLE');
        $em = $this->getEntityManager();
        $em->persist($role);
        $em->flush();

        $id = $role->getId();
        self::assertNotNull($id);

        $this->request($client, '/api/users/roles/' . $id, 'DELETE');

        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->getEntityManager()->clear();
        self::assertNotNull($this->getEntityManager()->getRepository(Role::class)->find($id), 'the role must not be deleted');
    }

    public function testDeleteRoleNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/users/roles/' . PHP_INT_MAX);
    }

    public function testDeleteRoleRemovesItFromUsers(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $role = new Role();
        $role->setName('TEST_ROLE');
        $em = $this->getEntityManager();
        $em->persist($role);

        $user = $this->getUserByRole(User::ROLE_USER);
        $user->addRole('TEST_ROLE');
        $em->persist($user);
        $em->flush();

        $id = $role->getId();
        self::assertNotNull($id);
        self::assertContains('TEST_ROLE', $this->getUserByRole(User::ROLE_USER)->getRoles());

        $this->request($client, '/api/users/roles/' . $id, 'DELETE');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->getEntityManager()->clear();
        self::assertNull($this->getEntityManager()->getRepository(Role::class)->find($id));
        self::assertNotContains('TEST_ROLE', $this->getUserByRole(User::ROLE_USER)->getRoles());
    }

    public function testDeleteRoleWithGetIsNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $role = new Role();
        $role->setName('TEST_ROLE');
        $em = $this->getEntityManager();
        $em->persist($role);
        $em->flush();

        $id = $role->getId();
        self::assertNotNull($id);

        $this->request($client, '/api/users/roles/' . $id, 'GET');

        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $this->getEntityManager()->clear();
        self::assertNotNull($this->getEntityManager()->getRepository(Role::class)->find($id));
    }
}
