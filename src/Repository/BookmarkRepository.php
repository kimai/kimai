<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Bookmark;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * @extends \Doctrine\ORM\EntityRepository<Bookmark>
 */
class BookmarkRepository extends EntityRepository
{
    public function saveBookmark(Bookmark $bookmark)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($bookmark);
        $entityManager->flush();
    }

    public function deleteBookmark(Bookmark $bookmark)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($bookmark);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    public function getSearchDefaultOptions(User $user, string $name): ?Bookmark
    {
        return $this->findOneBy([
            'user' => $user->getId(),
            'type' => Bookmark::SEARCH_DEFAULT,
            'name' => substr($name, 0, 50)
        ]);
    }
}
