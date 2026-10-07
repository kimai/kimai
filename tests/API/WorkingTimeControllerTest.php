<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeDay;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

#[Group('integration')]
class WorkingTimeControllerTest extends APIControllerBaseTestCase
{
    /**
     * @return array<mixed>
     */
    private function getJson(HttpKernelBrowser $client): array
    {
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);
        self::assertIsArray($result);

        return $result;
    }

    private function configureWorkContract(): void
    {
        /** @var UserRepository $repository */
        $repository = $this->getPrivateService(UserRepository::class);
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setWorkContractMode(WorkingTimeModeDay::ID);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY, '0');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY, '0');
        $repository->saveUser($user);
    }

    public function testIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/working-times/1/2026');
    }

    public function testGetOwnYearWithoutWorkContract(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->getAuthenticatedUserId(User::ROLE_USER);
        $this->request($client, '/api/working-times/' . $id . '/2026');
        $result = $this->getJson($client);

        self::assertSame($id, $result['user']);
        self::assertSame(2026, $result['year']);
        self::assertFalse($result['workContract']);
        self::assertSame(0, $result['expectedTime']);
        $months = $result['months'];
        self::assertIsArray($months);
        self::assertCount(12, $months);
        self::assertIsArray($months[0]);
        self::assertSame('2026-01', $months[0]['month']);
        self::assertSame([], $result['summaries']);
    }

    public function testGetOwnYearWithWorkContract(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->configureWorkContract();
        $id = $this->getAuthenticatedUserId(User::ROLE_USER);

        // 2026-01-01 is a Thursday: Jan 1, 2 and 5-9 are seven working days of 8 hours
        $this->request($client, '/api/working-times/' . $id . '/2026?until=2026-01-09');
        $result = $this->getJson($client);

        self::assertTrue($result['workContract']);
        self::assertSame('2026-01-09', $result['until']);
        self::assertSame(7 * 28800, $result['expectedTime']);
        $months = $result['months'];
        self::assertIsArray($months);
        self::assertIsArray($months[0]);
        self::assertIsArray($months[1]);
        self::assertSame(7 * 28800, $months[0]['expectedTime']);
        self::assertSame(0, $months[1]['expectedTime']);
        self::assertFalse($months[0]['locked']);
        self::assertNull($months[0]['lockDate']);
        self::assertNull($months[0]['lockedBy']);
    }

    public function testInvalidUntil(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->getAuthenticatedUserId(User::ROLE_USER);
        $this->assertBadRequest($client, '/api/working-times/' . $id . '/2026?until=2026-02-30', 'GET');
    }

    public function testUserCannotAccessOtherUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->getAuthenticatedUserId(User::ROLE_ADMIN);
        $this->assertApiAccessDenied($client, '/api/working-times/' . $id . '/2026');
    }

    public function testAdminCanAccessOtherUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $id = $this->getAuthenticatedUserId(User::ROLE_USER);
        $this->request($client, '/api/working-times/' . $id . '/2026');
        $result = $this->getJson($client);
        self::assertSame($id, $result['user']);
    }

    public function testUnknownUser(): void
    {
        $this->assertEntityNotFound(User::ROLE_ADMIN, '/api/working-times/' . PHP_INT_MAX . '/2026');
    }
}
