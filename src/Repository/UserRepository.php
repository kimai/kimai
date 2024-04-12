<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\Loader\UserLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\UserFormTypeQuery;
use App\Repository\Query\UserQuery;
use App\Utils\Pagination;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @extends \Doctrine\ORM\EntityRepository<User>
 * @template-implements PasswordUpgraderInterface<User>
 * @template-implements UserProviderInterface<User>
 */
class UserRepository extends EntityRepository implements UserLoaderInterface, UserProviderInterface, PasswordUpgraderInterface
{
    public function deleteUserPreference(UserPreference $preference, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($preference);
        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * @param User $user
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveUser(User $user): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!($user instanceof User) || !$user->isInternalUser()) {
            return;
        }

        if ($user->getPassword() === $newHashedPassword) {
            return;
        }

        try {
            $user->setPassword($newHashedPassword);
            $this->saveUser($user);
        } catch (\Exception $ex) {
            // happens during login: if it fails, ignore it!
        }
    }

    /**
     * Used to fetch a user by its ID.
     *
     * @param int $id
     * @return null|User
     */
    public function getUserById($id): ?User
    {
        /** @var User|null $user */
        $user = $this->find($id);

        if ($user !== null) {
            $loader = new UserLoader($this->getEntityManager(), true);
            $loader->loadResults([$user]);
        }

        return $user;
    }

    /**
     * @param int[] $userIds
     * @return User[]
     */
    public function findByIds(array $userIds): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->where($qb->expr()->in('u.id', ':id'))
            ->setParameter('id', $userIds)
        ;

        $users = $qb->getQuery()->getResult();

        $loader = new UserLoader($qb->getEntityManager(), true);
        $loader->loadResults($users);

        return $users;
    }

    /**
     * Overwritten to fetch preferences when using the Profile controller actions.
     * Depends on the query, some magic mechanisms like the ParamConverter will use this method to fetch the user.
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?object
    {
        if (\count($criteria) === 1 && isset($criteria['username'])) {
            return $this->loadUserByIdentifier($criteria['username']);
        }

        return parent::findOneBy($criteria, $orderBy);
    }

    public function findByUsername($username): ?User
    {
        return parent::findOneBy(['username' => $username]);
    }

    public function countUser(?bool $enabled = null): int
    {
        if (null !== $enabled) {
            return $this->count(['enabled' => $enabled]);
        }

        return $this->count([]);
    }

    /**
     * @param string $identifier
     * @return User
     * @throws UserNotFoundException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var User|null $user */
        $user = $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.username = :username')
            ->orWhere('u.email = :username')
            ->setParameter('username', $identifier)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $loader = new UserLoader($this->getEntityManager(), true);
        $loader->loadResults([$user]);

        return $user;
    }

    public function refreshUser(UserInterface $user): User
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    public function getQueryBuilderForFormType(UserFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if ($query->isShowVisible()) {
            $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', true, ParameterType::BOOLEAN);
        }

        $qb->andWhere($qb->expr()->eq('u.systemAccount', ':system'));
        $qb->setParameter('system', false, Types::BOOLEAN);
        $qb->addSelect("COALESCE(NULLIF(u.alias, ''), u.username) as HIDDEN userOrder");
        $qb->orderBy('userOrder', 'ASC');

        $this->addPermissionCriteria($qb, $query->getUser(), $query->getTeams());

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param User|null $user
     * @param Team[] $teams
     */
    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [])
    {
        // make sure that all queries without a user see all user
        if (null === $user && empty($teams)) {
            return;
        }

        // make sure that admins see all user
        if (null !== $user && $user->canSeeAllData()) {
            return;
        }

        $or = $qb->expr()->orX();

        // if no explicit team was requested and the user is part of some teams
        // then find all members of his teams (where he is teamlead)
        if (null !== $user && $user->isTeamlead()) {
            $userIds = [];
            foreach ($user->getTeams() as $team) {
                if ($team->isTeamlead($user)) {
                    foreach ($team->getUsers() as $teamMember) {
                        $userIds[] = $teamMember->getId();
                    }
                }
            }
            $userIds = array_unique($userIds);
            $qb->setParameter('teamMember', $userIds);
            $or->add($qb->expr()->in('u.id', ':teamMember'));
        }

        // if teams where requested, then select all team members
        if (\count($teams) > 0) {
            $userIds = [];
            foreach ($teams as $team) {
                foreach ($team->getUsers() as $teamMember) {
                    $userIds[] = $teamMember->getId();
                }
            }
            $userIds = array_unique($userIds);
            $qb->setParameter('userIds', $userIds);
            $or->add($qb->expr()->in('u.id', ':userIds'));
        }

        // and make sure, that the user himself is always returned
        if (null !== $user) {
            $or->add($qb->expr()->eq('u.id', ':self'));
            $qb->setParameter('self', $user);
        }

        if ($or->count() > 0) {
            $qb->andWhere($or);
        }
    }

    /**
     * @param string $role
     * @return User[]
     * @internal
     */
    public function findUsersWithRole(string $role): array
    {
        if ($role === User::ROLE_USER) {
            return $this->findAll();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->andWhere('u.roles LIKE :role');
        $qb->setParameter('role', '%' . $role . '%');

        return $qb->getQuery()->getResult();
    }

    private function getQueryBuilderForQuery(UserQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('u')
            ->from(User::class, 'u')
        ;

        foreach ($query->getOrderGroups() as $orderBy => $order) {
            switch ($orderBy) {
                default:
                    $orderBy = 'u.' . $orderBy;
                    break;
            }
            $qb->addOrderBy($orderBy, $order);
        }

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        if (\count($query->getSearchTeams()) > 0) {
            $userIds = [];
            foreach ($query->getSearchTeams() as $team) {
                foreach ($team->getUsers() as $user) {
                    $userIds[] = $user->getId();
                }
            }
            $qb->andWhere($qb->expr()->in('u.id', ':searchTeams'));
            $qb->setParameter('searchTeams', array_unique($userIds));
        }

        if ($query->isShowVisible()) {
            $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', true, ParameterType::BOOLEAN);
        } elseif ($query->isShowHidden()) {
            $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', false, ParameterType::BOOLEAN);
        }

        if ($query->getRole() !== null) {
            $rolesWhere = 'u.roles LIKE :role';
            $qb->setParameter('role', '%' . $query->getRole() . '%');
            // a workaround, because ROLE_USER is not saved in the database
            if ($query->getRole() === User::ROLE_USER) {
                $rolesWhere .= ' OR u.roles LIKE :role1';
                $qb->setParameter('role1', '%{}');
            }
            $qb->andWhere($rolesWhere);
        }

        if ($query->getSystemAccount() !== null) {
            $qb->andWhere($qb->expr()->eq('u.systemAccount', ':system'));
            $qb->setParameter('system', $query->getSystemAccount(), Types::BOOLEAN);
        }

        if ($query->hasSearchTerm()) {
            $searchAnd = $qb->expr()->andX();
            $searchTerm = $query->getSearchTerm();

            foreach ($searchTerm->getSearchFields() as $metaName => $metaValue) {
                $qb->leftJoin('u.preferences', 'meta');
                $searchAnd->add(
                    $qb->expr()->andX(
                        $qb->expr()->eq('meta.name', ':metaName'),
                        $qb->expr()->like('meta.value', ':metaValue')
                    )
                );
                $qb->setParameter('metaName', $metaName);
                $qb->setParameter('metaValue', '%' . $metaValue . '%');
            }

            if ($searchTerm->hasSearchTerm()) {
                $searchAnd->add(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.alias', ':searchTerm'),
                        $qb->expr()->like('u.title', ':searchTerm'),
                        $qb->expr()->like('u.accountNumber', ':searchTerm'),
                        $qb->expr()->like('u.email', ':searchTerm'),
                        $qb->expr()->like('u.username', ':searchTerm')
                    )
                );
                $qb->setParameter('searchTerm', '%' . $searchTerm->getSearchTerm() . '%');
            }

            if ($searchAnd->count() > 0) {
                $qb->andWhere($searchAnd);
            }
        }

        return $qb;
    }

    public function getPagerfantaForQuery(UserQuery $query): Pagination
    {
        return new Pagination($this->getPaginatorForQuery($query), $query);
    }

    public function countUsersForQuery(UserQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('u.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    protected function getPaginatorForQuery(UserQuery $query): PaginatorInterface
    {
        $counter = $this->countUsersForQuery($query);
        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new UserLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * @param UserQuery $query
     * @return User[]
     */
    public function getUsersForQuery(UserQuery $query): array
    {
        $qb = $this->getQueryBuilderForQuery($query);

        return $this->getHydratedResultsByQuery($qb);
    }

    /**
     * @param QueryBuilder $qb
     * @return User[]
     */
    protected function getHydratedResultsByQuery(QueryBuilder $qb): array
    {
        /** @var array<User> $results */
        $results = $qb->getQuery()->getResult();

        $loader = new UserLoader($qb->getEntityManager());
        $loader->loadResults($results);

        return $results;
    }

    public function deleteUser(User $delete, ?User $replace = null)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null !== $replace) {
                $qb = $em->createQueryBuilder();
                $qb
                    ->update(Timesheet::class, 't')
                    ->set('t.user', ':replace')
                    ->where('t.user = :delete')
                    ->setParameter('delete', $delete->getId())
                    ->setParameter('replace', $replace->getId())
                    ->getQuery()
                    ->execute();

                $qb = $em->createQueryBuilder();
                $qb
                    ->update(Invoice::class, 'i')
                    ->set('i.user', ':replace')
                    ->where('i.user = :delete')
                    ->setParameter('delete', $delete->getId())
                    ->setParameter('replace', $replace->getId())
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
}
