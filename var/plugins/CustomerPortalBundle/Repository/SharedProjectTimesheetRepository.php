<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Repository;

use App\Entity\Customer;
use App\Entity\Project;
use App\Repository\Paginator\QueryPaginator;
use App\Repository\ProjectRepository;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\ProjectQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;

/**
 * @extends EntityRepository<SharedProjectTimesheet>
 */
class SharedProjectTimesheetRepository extends EntityRepository
{
    public function findAllSharedProjects(BaseQuery $baseQuery): Pagination
    {
        $query = $this->createQueryBuilder('spt')
            ->leftJoin(Project::class, 'p', Join::WITH, 'spt.project = p')
            ->leftJoin(Customer::class, 'c', Join::WITH, 'spt.customer = c')
            ->orderBy('p.name, c.name, spt.shareKey', 'ASC')
            ->getQuery()
        ;

        $loader = new QueryPaginator($query, $this->count([]));

        return new Pagination($loader, $baseQuery);
    }

    public function save(SharedProjectTimesheet $sharedProject): void
    {
        $em = $this->getEntityManager();
        $em->persist($sharedProject);
        $em->flush();
    }

    public function remove(SharedProjectTimesheet $sharedProject): void
    {
        $em = $this->getEntityManager();
        $em->remove($sharedProject);
        $em->flush();
    }

    public function findByShareKey(string $shareKey): ?SharedProjectTimesheet
    {
        return $this->createQueryBuilder('spt')
            ->where('spt.shareKey = :shareKey')
            ->setMaxResults(1)
            ->setParameter('shareKey', $shareKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Project[]
     */
    public function getProjects(SharedProjectTimesheet $sharedProject): array
    {
        if ($sharedProject->getProject() !== null) {
            return [$sharedProject->getProject()];
        }

        if ($sharedProject->getCustomer() === null) {
            throw new \InvalidArgumentException('Unsupported, needs a customer');
        }

        /** @var ProjectRepository $projectRepository */
        $projectRepository = $this->_em->getRepository(Project::class); // @phpstan-ignore varTag.type

        $query = new ProjectQuery();
        $query->setCustomers([$sharedProject->getCustomer()]);

        return $projectRepository->getProjectsForQuery($query);
    }
}
