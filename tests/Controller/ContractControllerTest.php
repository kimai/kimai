<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;

/**
 * @group integration
 */
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
