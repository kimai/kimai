<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserLoader implements LoaderInterface
{
    private $entityManager;
    private $fullyHydrated;

    public function __construct(EntityManagerInterface $entityManager, bool $fullyHydrated = false)
    {
        $this->entityManager = $entityManager;
        $this->fullyHydrated = $fullyHydrated;
    }

    /**
     * @param User[] $users
     */
    public function loadResults(array $users): void
    {
        $ids = array_map(function (User $user) {
            return $user->getId();
        }, $users);

        $loader = new UserIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
