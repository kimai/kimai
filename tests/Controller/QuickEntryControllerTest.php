<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\QuickEntryMetaFieldSubscriberMock;
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
}
