<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Tests\KernelTestTrait;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A base test class for AbstractRepository implementations.
 */
abstract class AbstractRepositoryTestCase extends KernelTestCase
{
    use KernelTestTrait;

    private ?ObjectManager $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();

        /** @var Registry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        $this->entityManager = $doctrine->getManager();
    }

    protected function getEntityManager(): ObjectManager
    {
        return $this->entityManager;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->close();
        }
        $this->entityManager = null; // avoid memory leaks
    }
}
