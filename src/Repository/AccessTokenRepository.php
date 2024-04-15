<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\AccessToken;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<AccessToken>
 */
class AccessTokenRepository extends EntityRepository
{
    public function findByToken(string $token): ?AccessToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @return array<AccessToken>
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function saveAccessToken(AccessToken $accessToken): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($accessToken);
        $entityManager->flush();
    }

    public function deleteAccessToken(AccessToken $accessToken): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($accessToken);
        $entityManager->flush();
    }
}
