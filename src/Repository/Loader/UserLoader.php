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
    /**
     * @var UserIdLoader
     */
    private $loader;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new UserIdLoader($entityManager);
    }

    /**
     * @param User[] $users
     */
    public function loadResults(array $users): void
    {
        $ids = array_map(function (User $user) {
            return $user->getId();
        }, $users);

        $this->loader->loadResults($ids);
    }
}
