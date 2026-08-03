<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Event\UserInteractiveLoginEvent;
use App\EventSubscriber\LastLoginSubscriber;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(UserRepository::class)]
#[CoversClass(LastLoginSubscriber::class)]
#[Group('integration')]
class UserRepositoryTest extends AbstractRepositoryTestCase
{
    private function createUser(string $username): User
    {
        $user = new User();
        $user->setUserIdentifier($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword('foo');

        /** @var UserRepository $repository */
        $repository = $this->getEntityManager()->getRepository(User::class);
        $repository->saveUser($user);

        return $user;
    }

    /**
     * @phpstan-impure
     */
    private function getRawLastLogin(int $id): ?string
    {
        $result = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT last_login FROM kimai2_users WHERE id = ?',
            [$id],
            [Types::INTEGER]
        );

        return !\is_string($result) ? null : $result;
    }

    private function setRawLastLogin(int $id, \DateTime $date): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE kimai2_users SET last_login = ? WHERE id = ?',
            [$date, $id],
            [Types::DATETIME_MUTABLE, Types::INTEGER]
        );
    }

    /**
     * Reloads the user from the database, simulating a fresh request (e.g. a new API call),
     * where the entity is hydrated from the current database state instead of the in-memory object.
     */
    private function reloadUser(int $id): User
    {
        $em = $this->getEntityManager();
        $em->clear();

        /** @var UserRepository $repository */
        $repository = $em->getRepository(User::class);
        $user = $repository->getUserById($id);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * Full flow: two subsequent "API calls" for the same user.
     * The first login writes the "last_login" field, the second one happens within the
     * 15 minute window and must NOT trigger another write.
     */
    public function testLastLoginIsWrittenOnceWithinTimeWindow(): void
    {
        $user = $this->createUser('john_flow');
        $id = $user->getId();
        self::assertIsInt($id);
        self::assertNull($user->getLastLogin());
        self::assertNull($this->getRawLastLogin($id));

        $subscriber = new LastLoginSubscriber($this->getEntityManager()->getRepository(User::class));

        // first request: the field is empty, so it gets written to the database
        $firstUser = $this->reloadUser($id);
        $subscriber->onImplicitLogin(new UserInteractiveLoginEvent($firstUser));

        $firstLogin = $this->getRawLastLogin($id);
        self::assertNotNull($firstLogin);

        // the in-memory entity is intentionally NOT modified (no changeset computation)
        self::assertNull($firstUser->getLastLogin());

        // second request: the freshly loaded user has a recent "last_login" (< 15 minutes),
        // so the repository must skip the write and the stored value stays untouched
        $secondUser = $this->reloadUser($id);
        self::assertNotNull($secondUser->getLastLogin());
        $subscriber->onImplicitLogin(new UserInteractiveLoginEvent($secondUser));

        self::assertSame($firstLogin, $this->getRawLastLogin($id));
    }

    /**
     * Full flow: a user whose last login is older than the time window.
     * The "last_login" was set to 20 minutes in the past directly in the database,
     * so the next login must update it.
     */
    public function testLastLoginIsUpdatedWhenOutdated(): void
    {
        $user = $this->createUser('jane_flow');
        $id = $user->getId();
        self::assertIsInt($id);

        // simulate a login that happened 20 minutes ago (outside the 15 minute window)
        $twentyMinutesAgo = new \DateTime('-20 minute', $user->getDateTimezone());
        $this->setRawLastLogin($id, $twentyMinutesAgo);
        $storedBefore = $this->getRawLastLogin($id);
        self::assertNotNull($storedBefore);

        $subscriber = new LastLoginSubscriber($this->getEntityManager()->getRepository(User::class));

        $freshUser = $this->reloadUser($id);
        $lastLogin = $freshUser->getLastLogin();
        self::assertNotNull($lastLogin);
        self::assertEqualsWithDelta($twentyMinutesAgo->getTimestamp(), $lastLogin->getTimestamp(), 5);

        $subscriber->onImplicitLogin(new UserInteractiveLoginEvent($freshUser));

        // the outdated value was replaced with a recent one
        $storedAfter = $this->getRawLastLogin($id);
        self::assertNotNull($storedAfter);
        self::assertNotSame($storedBefore, $storedAfter);

        $updated = $this->reloadUser($id)->getLastLogin();
        self::assertNotNull($updated);
        self::assertGreaterThan($twentyMinutesAgo, $updated);
        self::assertEqualsWithDelta((new \DateTime())->getTimestamp(), $updated->getTimestamp(), 60);
    }
}
