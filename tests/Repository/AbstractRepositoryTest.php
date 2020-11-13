<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Tests\KernelTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A base test class for AbstractRepository implementations.
 */
abstract class AbstractRepositoryTest extends KernelTestCase
{
    use KernelTestTrait;

    /**
     * @var ObjectManager|null
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->close();
        }
        $this->entityManager = null; // avoid memory leaks
    }
}
