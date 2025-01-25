<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Tag;
use App\Entity\User;
use App\Tests\DataFixtures\TagFixtures;

/**
 * @group integration
 */
class TagControllerTest extends AbstractControllerBaseTestCase
{
    /**
     * @return Tag[]
     */
    protected function importTags(): array
    {
        $tagList = ['Test', 'Administration', 'Support', '#2018-001', '#2018-002', '#2018-003', 'Development',
            'Marketing', 'First Level Support', 'Bug Fixing'];

        $fixture = new TagFixtures();
        $fixture->setTagArray($tagList);

        return $this->importFixture($fixture);
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/tags/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importTags();
        $this->assertAccessIsGranted($client, '/admin/tags/');

        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_admin_tags', 10);
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importTags();

        $this->request($client, '/admin/tags/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'Support',
        ]);

        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_admin_tags', 2);
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/tags/create');
        $form = $client->getCrawler()->filter('form[name=tag_edit_form]')->form();
        $client->submit($form, [
            'tag_edit_form' => [
                'name' => 'A tAG Name!',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/tags/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);

        $id = $this->getEntityManager()->getRepository(Tag::class)->findAll()[0]->getId();

        $this->request($client, '/admin/tags/' . $id . '/edit');
        $editForm = $client->getCrawler()->filter('form[name=tag_edit_form]')->form();
        self::assertEquals('A tAG Name!', $editForm->get('tag_edit_form[name]')->getValue());
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $tags = $this->importTags();
        $id = $tags[0]->getId();

        $this->assertAccessIsGranted($client, '/admin/tags/' . $id . '/edit');
        $form = $client->getCrawler()->filter('form[name=tag_edit_form]')->form();
        $client->submit($form, [
            'tag_edit_form' => ['name' => 'Test 2 updated']
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/tags/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/tags/' . $id . '/edit');
        $editForm = $client->getCrawler()->filter('form[name=tag_edit_form]')->form();
        self::assertEquals('Test 2 updated', $editForm->get('tag_edit_form[name]')->getValue());
    }

    public function testMultiDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importTags();

        $this->assertAccessIsGranted($client, '/admin/tags/');

        $form = $client->getCrawler()->filter('form[name=multi_update_table]')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/admin/tags/multi-delete'));

        $em = $this->getEntityManager();
        /** @var Tag[] $tags */
        $tags = $em->getRepository(Tag::class)->findAll();
        self::assertCount(10, $tags);
        $ids = [];
        foreach ($tags as $tag) {
            $ids[] = $tag->getId();
        }

        $client->submit($form, [
            'multi_update_table' => [
                'entities' => implode(',', $ids)
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/tags/'));
        $client->followRedirect();

        $em->clear();
        self::assertEquals(0, $em->getRepository(Tag::class)->count([]));
    }
}
