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
class TagRepositoryTest extends AbstractRepositoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $em = $this->getEntityManager();
        $data = new TagFixtures();
        $data->setTagArray(['Test', 'Travel', '#2018-001', '#2018-002', '#2018-003', '#2018-004', '#2018-005', 'Administration', 'Support', 'PR', '#2018-012']);
        $this->importFixture($data);
    }

    public function testFindAllTagNames(): void
    {
        $em = $this->getEntityManager();
        /** @var TagRepository $repository */
        $repository = $em->getRepository(Tag::class);

        $result = $repository->findAllTagNames('2018');
        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(6, \count($result));
        self::assertEquals('#2018-001', $result[0]);
        self::assertEquals('#2018-002', $result[1]);
        self::assertEquals('#2018-003', $result[2]);
        self::assertEquals('#2018-004', $result[3]);
        self::assertEquals('#2018-005', $result[4]);
        self::assertEquals('#2018-012', $result[5]);
    }

    public function testFindNoTagNames(): void
    {
        $em = $this->getEntityManager();
        /** @var TagRepository $repository */
        $repository = $em->getRepository(Tag::class);

        $result = $repository->findAllTagNames('Nothing');
        self::assertIsArray($result);
        self::assertEmpty($result);
        self::assertEquals(0, \count($result));
    }
}
