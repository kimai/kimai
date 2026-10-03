<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\QuickEntryMetaFieldSubscriberMock;
use App\User\PermissionService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[Group('integration')]
class QuickEntryControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/quick_entry/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/quick_entry/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('form[name=quick_entry_weekrange_form]');
        self::assertEquals(1, $node->filter('div.btn-group.week-picker-btn-group')->count());

        $node = $client->getCrawler()->filter('section.content form[name=quick_entry_form]');
        self::assertEquals(1, $node->filter('input.btn-primary[type=submit]')->count());

        $addBtn = $node->filter('button.btn-success[type=button]');
        self::assertEquals(1, $addBtn->count());
        self::assertNotNull($addBtn->attr('data-collection-prototype'));
        self::assertNotNull($addBtn->attr('data-collection-holder'));

        $rows = $client->getCrawler()->filter('section.content form[name=quick_entry_form] table.dataTable tbody tr:not(.summary)');
        self::assertEquals(3, $rows->count());
        $validate = $rows->getIterator()[0];
        $columns = [];
        foreach ($validate->childNodes as $childNode) {
            if ($childNode instanceof \DOMText) {
                continue;
            }
            if ($childNode instanceof \DOMElement && $childNode->tagName === 'td') {
                $columns[] = $childNode;
            }
        }
        // project + activity + 7 days (duration) + row totals
        self::assertCount(10, $columns);
    }

    public function testIndexActionWith(): void
    {
        $client = $this->getClientForAuthenticatedUser();

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(50);
        $fixture->setUser($this->getUserByRole());
        $fixture->setStartDate(new \DateTime('-7 days'));
        $this->importFixture($fixture);

        $this->request($client, '/quick_entry/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('form[name=quick_entry_form]');
        self::assertEquals(1, $node->filter('input.btn-primary[type=submit]')->count());

        $addBtn = $node->filter('button.btn-success[type=button]');
        self::assertEquals(1, $addBtn->count());
        self::assertNotNull($addBtn->attr('data-collection-prototype'));
        self::assertNotNull($addBtn->attr('data-collection-holder'));

        $rows = $client->getCrawler()->filter('section.content form[name=quick_entry_form] table.dataTable tbody tr:not(.summary)');
        self::assertGreaterThanOrEqual(3, $rows->count());
        $validate = $rows->getIterator()[0];
        $columns = [];
        foreach ($validate->childNodes as $childNode) {
            if ($childNode instanceof \DOMText) {
                continue;
            }
            if ($childNode instanceof \DOMElement && $childNode->tagName === 'td') {
                $columns[] = $childNode;
            }
        }
        // project + activity + 7 days (duration) + row totals
        self::assertCount(10, $columns);
    }

    public function testTimesheetsAreNotMergedWhenMetaFieldValuesDiffer(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $user = $this->getUserByRole(User::ROLE_USER);

        $customers = new CustomerFixtures();
        $customers->setIsVisible(true);
        $customers->setAmount(1);
        $customers = $this->importFixture($customers);

        $projects = new ProjectFixtures();
        $projects->setCustomers($customers);
        $projects->setIsVisible(true);
        $projects->setAmount(1);
        $projects = $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setIsGlobal(true);
        $activities->setIsVisible(true);
        $activities->setAmount(1);
        $activities = $this->importFixture($activities);

        // two entries sharing the same project and activity, on different days of the same
        // week, but with different values for the "location" meta-field
        $begins = [new \DateTime('2020-05-12 10:00:00'), new \DateTime('2020-05-14 10:00:00')];
        $locations = ['office', 'homeoffice'];
        $counter = 0;

        $timesheets = new TimesheetFixtures();
        $timesheets->setUser($user);
        $timesheets->setProjects($projects);
        $timesheets->setActivities($activities);
        $timesheets->setAmount(2);
        $timesheets->setCallback(function (Timesheet $timesheet) use (&$counter, $begins, $locations): void {
            $begin = clone $begins[$counter];
            $end = clone $begin;
            $end->modify('+1 hour');
            $timesheet->setBegin($begin);
            $timesheet->setEnd($end);
            $timesheet->setDuration(3600);
            $timesheet->setMetaField((new TimesheetMeta())->setName('location')->setValue($locations[$counter]));
            $counter++;
        });
        $this->importFixture($timesheets);

        // register the "location" meta-field as a QuickEntry column, so it participates in the grouping
        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new QuickEntryMetaFieldSubscriberMock());

        // the "date" query parameter forces the view to the week containing both entries
        $this->request($client, '/quick_entry/?date=2020-05-13');
        self::assertTrue($client->getResponse()->isSuccessful());

        $metaValues = $client->getCrawler()
            ->filter('form[name=quick_entry_form] input[name$="[metaFields][location][value]"]')
            ->each(function ($node) {
                return $node->attr('value');
            });
        $metaValues = array_values(array_filter($metaValues, static fn ($value) => $value !== null && $value !== ''));

        // before the fix both timesheets were merged into a single row and one of the meta values
        // was lost - now each distinct meta value must be represented by its own row
        self::assertContains('office', $metaValues, 'The "office" entry must be shown in its own row');
        self::assertContains('homeoffice', $metaValues, 'The "homeoffice" entry must be shown in its own row');
    }

    /**
     * Regression test for GHSA-2w7f-x78f-89q2.
     *
     * A user with "view_other_timesheet" and "edit_other_timesheet" but without
     * "create_other_timesheet" must not be able to create new records for the
     * members of the teams they lead.
     */
    public function testQuickEntryCannotCreateTimesheetForOtherUserWithoutPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $victim = $this->prepareTeamWithVictim(false);
        $victimId = $victim->getId();
        self::assertIsInt($victimId);

        $url = '/quick_entry/?user=' . $victimId . '&date=2020-05-11';
        $this->request($client, $url);
        self::assertTrue($client->getResponse()->isSuccessful());

        // a submit which sets a duration for one of the empty cells must not create a record
        $form = $client->getCrawler()->filter('form[name=quick_entry_form]')->form();
        $values = $form->getPhpValues();
        $values['quick_entry_form']['rows'][0]['timesheets'][1]['duration'] = '0:30';

        $client->request('POST', $this->createUrl($url), $values);

        self::assertCount(1, $this->findTimesheets($victim));
    }

    /**
     * Regression test for GHSA-2w7f-x78f-89q2: cells for records which cannot be
     * created are rendered disabled, so they are ignored on submit.
     */
    public function testQuickEntryDisablesNewCellsForOtherUserWithoutPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $victim = $this->prepareTeamWithVictim(false);
        $victimId = $victim->getId();
        self::assertIsInt($victimId);

        $this->request($client, '/quick_entry/?user=' . $victimId . '&date=2020-05-11');
        self::assertTrue($client->getResponse()->isSuccessful());

        $disabled = $client->getCrawler()
            ->filter('form[name=quick_entry_form] input[name$="[duration]"]')
            ->each(fn ($node) => $node->attr('disabled') !== null);

        self::assertContains(true, $disabled, 'New timesheet cells must be disabled without create_other_timesheet');
    }

    /**
     * Control for testQuickEntryCannotCreateTimesheetForOtherUserWithoutPermission():
     * with the permission granted, the very same request creates the record.
     */
    public function testQuickEntryCreatesTimesheetForOtherUserWithPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $victim = $this->prepareTeamWithVictim(true);
        $victimId = $victim->getId();
        self::assertIsInt($victimId);

        $url = '/quick_entry/?user=' . $victimId . '&date=2020-05-11';
        $this->request($client, $url);
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=quick_entry_form]')->form();
        $values = $form->getPhpValues();
        $values['quick_entry_form']['rows'][0]['timesheets'][1]['duration'] = '0:30';

        $client->request('POST', $this->createUrl($url), $values);

        self::assertCount(2, $this->findTimesheets($victim));
    }

    /**
     * Reproduction + billing check for BUG-5348: two quick-entry rows entered for the same day
     * must chain back to back instead of both landing on the configured default start time.
     */
    public function testTwoRowsOnTheSameDayChainInsteadOfOverlapping(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_timesheet]')->form();
        $client->submit($form, [
            'system_configuration_form_timesheet' => [
                'configuration' => [
                    ['name' => 'timesheet.mode', 'value' => 'default'],
                    ['name' => 'timesheet.default_begin', 'value' => '09:00'],
                    ['name' => 'timesheet.rules.allow_future_times', 'value' => true],
                    ['name' => 'timesheet.rules.allow_zero_duration', 'value' => true],
                    ['name' => 'timesheet.rules.allow_overlapping_records', 'value' => false],
                    ['name' => 'timesheet.rules.allow_overbooking_budget', 'value' => true],
                    ['name' => 'timesheet.active_entries.hard_limit', 'value' => 1],
                ],
            ],
        ]);

        $customers = new CustomerFixtures();
        $customers->setIsVisible(true);
        $customers->setAmount(1);
        $customers = $this->importFixture($customers);

        $projects = new ProjectFixtures();
        $projects->setCustomers($customers);
        $projects->setIsVisible(true);
        $projects->setAmount(2);
        $projects = $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setIsGlobal(true);
        $activities->setIsVisible(true);
        $activities->setAmount(1);
        $activities = $this->importFixture($activities);

        $user = $this->getUserByRole(User::ROLE_SUPER_ADMIN);
        $wednesday = (new \DateTime('monday this week'))->modify('+2 days');

        $url = '/quick_entry/?date=' . $wednesday->format('Y-m-d');
        $this->request($client, $url);
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=quick_entry_form]')->form();
        $values = $form->getPhpValues();
        $values['quick_entry_form']['rows'][0]['project'] = (string) $projects[0]->getId();
        $values['quick_entry_form']['rows'][0]['activity'] = (string) $activities[0]->getId();
        $values['quick_entry_form']['rows'][0]['timesheets'][2]['duration'] = '2:00';
        $values['quick_entry_form']['rows'][1]['project'] = (string) $projects[1]->getId();
        $values['quick_entry_form']['rows'][1]['activity'] = (string) $activities[0]->getId();
        $values['quick_entry_form']['rows'][1]['timesheets'][2]['duration'] = '3:00';

        $client->request('POST', $this->createUrl($url), $values);
        $this->assertIsRedirect($client);
        $client->followRedirect();
        $this->assertHasFlashSuccess($client);

        $stored = $this->findTimesheets($user);
        self::assertCount(2, $stored);

        usort($stored, fn (Timesheet $a, Timesheet $b) => $a->getBegin() <=> $b->getBegin());

        self::assertSame('09:00', $stored[0]->getBegin()?->format('H:i'));
        self::assertSame('11:00', $stored[0]->getEnd()?->format('H:i'));
        self::assertSame('11:00', $stored[1]->getBegin()?->format('H:i'));
        self::assertSame('14:00', $stored[1]->getEnd()?->format('H:i'));

        // billing check: 5 distinct hours, never 5 hours squeezed into a 3-hour span
        self::assertSame(7200, $stored[0]->getDuration());
        self::assertSame(10800, $stored[1]->getDuration());
    }

    /**
     * @return array<Timesheet>
     */
    private function findTimesheets(User $user): array
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(Timesheet::class)->findBy(['user' => $user->getId()]);
    }

    /**
     * Creates a team, whose teamlead is the ROLE_TEAMLEAD user and which contains the
     * ROLE_USER user with one existing timesheet in the week of 2020-05-11.
     */
    private function prepareTeamWithVictim(bool $allowCreateOther): User
    {
        $em = $this->getEntityManager();

        $permissionService = self::getContainer()->get(PermissionService::class);
        self::assertInstanceOf(PermissionService::class, $permissionService);

        $role = (new Role())->setName(User::ROLE_TEAMLEAD);
        $em->persist($role);
        $permissionService->saveRolePermission(
            (new RolePermission())->setRole($role)->setPermission('create_other_timesheet')->setAllowed($allowCreateOther)
        );

        $attacker = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $victim = $this->getUserByRole(User::ROLE_USER);

        $team = new Team('GHSA-2w7f team');
        $team->addTeamlead($attacker);
        $team->addUser($victim);
        $em->persist($team);
        $em->flush();

        $customers = new CustomerFixtures();
        $customers->setIsVisible(true);
        $customers->setAmount(1);
        $customers = $this->importFixture($customers);

        $projects = new ProjectFixtures();
        $projects->setCustomers($customers);
        $projects->setIsVisible(true);
        $projects->setAmount(1);
        $projects = $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setIsGlobal(true);
        $activities->setIsVisible(true);
        $activities->setAmount(1);
        $activities = $this->importFixture($activities);

        $timesheets = new TimesheetFixtures();
        $timesheets->setUser($victim);
        $timesheets->setProjects($projects);
        $timesheets->setActivities($activities);
        $timesheets->setAmount(1);
        $timesheets->setCallback(function (Timesheet $timesheet): void {
            $timesheet->setBegin(new \DateTime('2020-05-11 10:00:00'));
            $timesheet->setEnd(new \DateTime('2020-05-11 11:00:00'));
            $timesheet->setDuration(3600);
        });
        $this->importFixture($timesheets);

        return $victim;
    }
}
