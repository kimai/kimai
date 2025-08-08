<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\ICSCalendarSource;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ICSCalendarSource>
 *
 * @method ICSCalendarSource|null find($id, $lockMode = null, $lockVersion = null)
 * @method ICSCalendarSource|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ICSCalendarSource[]    findAll()
 * @method ICSCalendarSource[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class ICSCalendarSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ICSCalendarSource::class);
    }

    /**
     * @return ICSCalendarSource[]
     */
    public function findEnabledForUser(User $user): array
    {
        return $this->createQueryBuilder('ics')
            ->andWhere('ics.user = :user')
            ->andWhere('ics.enabled = :enabled')
            ->setParameter('user', $user)
            ->setParameter('enabled', true)
            ->orderBy('ics.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ICSCalendarSource $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ICSCalendarSource $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 