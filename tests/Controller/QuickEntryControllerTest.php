<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @group integration
 */
class QuickEntryControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/quick_entry');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/quick_entry');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('section.content form[name=quick_entry_form]');
        self::assertEquals(1, $node->filter('div.btn-group.week-picker-btn-group')->count());
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

        $this->request($client, '/quick_entry');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('section.content form[name=quick_entry_form]');
        self::assertEquals(1, $node->filter('div.btn-group.week-picker-btn-group')->count());
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
}
