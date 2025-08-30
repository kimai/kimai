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
use App\Repository\UserRepository;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeDay;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class ContractControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/contract');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/contract');
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('No target hours have been configured', $content);
        $node = $client->getCrawler()->filter('select#user');
        self::assertEquals(0, $node->count());
    }

    public function testIndexActionWithWorkContract(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        /** @var UserRepository $repository */
        $repository = $this->getPrivateService(UserRepository::class);
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setWorkContractMode(WorkingTimeModeDay::ID);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY, '28800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY, '25200');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY, '19800');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY, '0');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY, '0');
        $repository->saveUser($user);

        $this->assertAccessIsGranted($client, '/contract');
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $node = $client->getCrawler()->filter('table#working_times_details');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('7:00', $content);
        self::assertStringContainsString('8:00', $content);
        self::assertStringContainsString('5:30', $content);
    }

    public function testTeamleadCanChangeUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/contract');
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('No target hours have been configured', $content);
        $node = $client->getCrawler()->filter('select#user');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('a.alert-link');
        self::assertEquals(0, $node->count());
    }

    public function testAdminCanConfigureUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/contract');
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('No target hours have been configured', $content);
        $node = $client->getCrawler()->filter('select#user');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('a.alert-link');
        self::assertEquals(1, $node->count());
    }
}
