<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Tests\DataFixtures\TagFixtures;

/**
 * @covers \App\Repository\TagRepository
 * @group integration
 */
class TagRepositoryTest extends AbstractRepositoryTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $em = $this->getEntityManager();
        $data = new TagFixtures();
        $data->setTagArray(['Test', 'Travel', '#2018-001', '#2018-002', '#2018-003', '#2018-004', '#2018-005', 'Administration', 'Support', 'PR', '#2018-012']);
        $this->importFixture($data);
    }

    public function testFindIds()
    {
        $em = $this->getEntityManager();
        /** @var TagRepository $repository */
        $repository = $em->getRepository(Tag::class);

        $result = $repository->findIdsByTagNameList('2018,Test');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, \count($result));
        $this->assertEquals(1, $result[0]);
        $this->assertEquals(3, $result[1]);
        $this->assertEquals(4, $result[2]);
        $this->assertEquals(5, $result[3]);
        $this->assertEquals(6, $result[4]);
        $this->assertEquals(7, $result[5]);
        $this->assertEquals(11, $result[6]);
    }

    public function testFindNoIds()
    {
        $em = $this->getEntityManager();
        /** @var TagRepository $repository */
        $repository = $em->getRepository(Tag::class);

        $result = $repository->findIdsByTagNameList('Simply');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertEquals(0, \count($result));
    }

    public function testFindAllTagNames()
    {
        $em = $this->getEntityManager();
        /** @var TagRepository $repository */
        $repository = $em->getRepository(Tag::class);

        $result = $repository->findAllTagNames('2018');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(6, \count($result));
        $this->assertEquals('#2018-001', $result[0]);
        $this->assertEquals('#2018-002', $result[1]);
        $this->assertEquals('#2018-003', $result[2]);
        $this->assertEquals('#2018-004', $result[3]);
        $this->assertEquals('#2018-005', $result[4]);
        $this->assertEquals('#2018-012', $result[5]);
    }

    public function testFindNoTagNames()
    {
        $em = $this->getEntityManager();
        /** @var TagRepository $repository */
        $repository = $em->getRepository(Tag::class);

        $result = $repository->findAllTagNames('Nothing');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertEquals(0, \count($result));
    }
}
