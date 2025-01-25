<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\CustomerComment;
use App\Entity\CustomerMeta;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\Loader\CustomerLoader;
use App\Repository\Paginator\LoaderQueryPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\CustomerQueryHydrate;
use App\Utils\Pagination;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Customer>
 */
class CustomerRepository extends EntityRepository
{
    use RepositorySearchTrait;

    /**
     * @param int[] $customerIDs
     * @return array<Customer>
     */
    public function findByIds(array $customerIDs): array
    {
        $ids = array_filter(
            array_unique($customerIDs),
            function ($value) {
                return $value > 0;
            }
        );

        if (\count($ids) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('c');
        $qb
            ->where($qb->expr()->in('c.id', ':id'))
            ->setParameter('id', $ids)
        ;

        return $this->getCustomers($this->prepareCustomerQuery($qb->getQuery()), new CustomerQuery());
    }

    public function saveCustomer(Customer $customer): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($customer);
        $entityManager->flush();
    }

    public function countCustomer(bool $visible = false): int
    {
        if ($visible) {
            return $this->count(['visible' => $visible]);
        }

        return $this->count([]);
    }

    public function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = []): void
    {
        $permissions = $this->getPermissionCriteria($qb, $user, $teams);
        if ($permissions->count() > 0) {
            $qb->andWhere($permissions);
        }
    }

    private function getPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = []): Andx
    {
        $andX = $qb->expr()->andX();

        // make sure that all queries without a user see all customers
        if (null === $user && empty($teams)) {
            return $andX;
        }

        // make sure that admins see all customers
        if (null !== $user && $user->canSeeAllData()) {
            return $andX;
        }

        if (null !== $user) {
            $teams = array_merge($teams, $user->getTeams());
        }

        if (empty($teams)) {
            $andX->add('SIZE(c.teams) = 0');

            return $andX;
        }

        $or = $qb->expr()->orX(
            'SIZE(c.teams) = 0',
            $qb->expr()->isMemberOf(':teams', 'c.teams')
        );
        $andX->add($or);

        $ids = array_values(array_unique(array_map(function (Team $team) {
            return $team->getId();
        }, $teams)));

        $qb->setParameter('teams', $ids);

        return $andX;
    }

    /**
     * Returns a query builder that is used for CustomerType and your own 'query_builder' option.
     *
     * @internal
     */
    public function getQueryBuilderForFormType(CustomerFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c')
            ->from(Customer::class, 'c')
            ->orderBy('c.name', 'ASC');

        $mainQuery = $qb->expr()->andX();

        $mainQuery->add($qb->expr()->eq('c.visible', ':visible'));
        $qb->setParameter('visible', true, ParameterType::BOOLEAN);

        $permissions = $this->getPermissionCriteria($qb, $query->getUser(), $query->getTeams());
        if ($permissions->count() > 0) {
            $mainQuery->add($permissions);
        }

        $outerQuery = $qb->expr()->orX();

        // this is a risk, as a user can manipulate the query and inject IDs that would be hidden otherwise
        if ($query->isAllowCustomerPreselect() && $query->hasCustomers()) {
            $outerQuery->add($qb->expr()->in('c.id', ':customer'));
            $qb->setParameter('customer', $query->getCustomers());
        }

        if (null !== $query->getCustomerToIgnore()) {
            $mainQuery = $qb->expr()->andX(
                $mainQuery,
                $qb->expr()->neq('c.id', ':ignored')
            );
            $qb->setParameter('ignored', $query->getCustomerToIgnore());
        }

        $outerQuery->add($mainQuery);
        $qb->andWhere($outerQuery);

        return $qb;
    }

    private function getQueryBuilderForQuery(CustomerQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        if (\count($query->getCustomerIds()) > 0) {
            $qb->andWhere($qb->expr()->in('c.id', ':id'))->setParameter('id', $query->getCustomerIds());
        }

        if ($query->getCountry() !== null) {
            $qb->andWhere($qb->expr()->eq('c.country', ':country'))->setParameter('country', $query->getCountry());
        }

        foreach ($query->getOrderGroups() as $orderBy => $order) {
            switch ($orderBy) {
                case 'vat_id':
                    $orderBy = 'c.vatId';
                    break;
                default:
                    $orderBy = 'c.' . $orderBy;
                    break;
            }
            $qb->addOrderBy($orderBy, $order);
        }

        if ($query->isShowVisible()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'))->setParameter('visible', true, ParameterType::BOOLEAN);
        } elseif ($query->isShowHidden()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'))->setParameter('visible', false, ParameterType::BOOLEAN);
        }

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        $this->addSearchTerm($qb, $query);

        return $qb;
    }

    private function getMetaFieldClass(): string
    {
        return CustomerMeta::class;
    }

    private function getMetaFieldName(): string
    {
        return 'customer';
    }

    /**
     * @return array<string>
     */
    private function getSearchableFields(): array
    {
        return ['c.name', 'c.comment', 'c.company', 'c.vatId', 'c.number', 'c.contact', 'c.phone', 'c.email', 'c.address'];
    }

    public function getPagerfantaForQuery(CustomerQuery $query): Pagination
    {
        return new Pagination($this->getPaginatorForQuery($query), $query);
    }

    /**
     * FIXME make this private and remove the widget that this currently uses
     * @return int<0, max>
     */
    public function countCustomersForQuery(CustomerQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select($qb->expr()->countDistinct('c.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult(); // @phpstan-ignore-line
    }

    /**
     * @return PaginatorInterface<Customer>
     */
    private function getPaginatorForQuery(CustomerQuery $customerQuery): PaginatorInterface
    {
        $counter = $this->countCustomersForQuery($customerQuery);
        $query = $this->createCustomerQuery($customerQuery);

        return new LoaderQueryPaginator(new CustomerLoader($this->getEntityManager(), $customerQuery), $query, $counter);
    }

    /**
     * @return Query<Customer>
     */
    private function createCustomerQuery(CustomerQuery $customerQuery): Query
    {
        $query = $this->getQueryBuilderForQuery($customerQuery)->getQuery();
        $query = $this->prepareCustomerQuery($query);

        foreach ($customerQuery->getHydrate() as $hydrate) {
            switch ($hydrate) {
                case CustomerQueryHydrate::TEAMS:
                    // does not yet work, see https://github.com/doctrine/orm/pull/8391
                    // $query->setFetchMode(Customer::class, 'teams', ClassMetadata::FETCH_EAGER);
                    break;

                case CustomerQueryHydrate::TEAM_MEMBER:
                    // does not yet work, see https://github.com/doctrine/orm/issues/11254
                    // $query->setFetchMode(Customer::class, 'teams', ClassMetadata::FETCH_EAGER);
                    // $query->setFetchMode(Team::class, 'members', ClassMetadata::FETCH_EAGER);
                    // $query->setFetchMode(TeamMember::class, 'user', ClassMetadata::FETCH_EAGER);
                    break;
            }
        }

        return $query;
    }

    /**
     * @param Query<Customer> $query
     * @return Query<Customer>
     */
    public function prepareCustomerQuery(Query $query): Query
    {
        $this->getEntityManager()->getConfiguration()->setEagerFetchBatchSize(300);

        $query->setFetchMode(Customer::class, 'meta', ClassMetadata::FETCH_EAGER);

        return $query;
    }

    /**
     * @return Customer[]
     */
    public function getCustomersForQuery(CustomerQuery $customerQuery): array
    {
        return $this->getCustomers($this->createCustomerQuery($customerQuery), $customerQuery);
    }

    /**
     * @param Query<Customer> $query
     * @return Customer[]
     */
    public function getCustomers(Query $query, CustomerQuery $customerQuery): array
    {
        /** @var array<Customer> $customers */
        $customers = $query->execute();

        $loader = new CustomerLoader($this->getEntityManager(), $customerQuery);
        $loader->loadResults($customers);

        return $customers;
    }

    public function deleteCustomer(Customer $delete, ?Customer $replace = null): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null !== $replace) {
                $qb = $em->createQueryBuilder();
                $qb
                    ->update(Project::class, 'p')
                    ->set('p.customer', ':replace')
                    ->where('p.customer = :delete')
                    ->setParameter('delete', $delete)
                    ->setParameter('replace', $replace)
                    ->getQuery()
                    ->execute();
            }

            $em->remove($delete);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    public function getComments(Customer $customer): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('comments')
            ->from(CustomerComment::class, 'comments')
            ->andWhere($qb->expr()->eq('comments.customer', ':customer'))
            ->addOrderBy('comments.pinned', 'DESC')
            ->addOrderBy('comments.createdAt', 'DESC')
            ->setParameter('customer', $customer)
        ;

        return $qb->getQuery()->getResult();
    }

    public function saveComment(CustomerComment $comment): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($comment);
        $entityManager->flush();
    }

    public function deleteComment(CustomerComment $comment): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($comment);
        $entityManager->flush();
    }
}
