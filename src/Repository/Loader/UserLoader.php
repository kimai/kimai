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
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false)
    {
    }

    /**
     * @param User[] $results
     */
    public function loadResults(array $results): void
    {
        $ids = array_map(function (User $user) {
            return $user->getId();
        }, $results);

        $loader = new UserIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
