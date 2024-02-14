<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\AccessToken;
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
