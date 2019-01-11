<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 2019-01-07
 * Time: 07:14
 */

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transformationsklasse für die Verarbeitung von Tag-Objekten zu einer Liste von Strings
 *
 * @package App\Form\DataTransformer
 */
class TagArrayToStringTransformer implements DataTransformerInterface {

  /** @var EntityManagerInterface */
  private $entityManager;

  public function _construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * Transforms an object (issue) to a string (number).
   *
   * @param  Tag[]|ArrayCollection|null $tags
   *
   * @return string
   */
  public function transform($tags) {
    if (NULL === $tags) {
      return '';
    }

    return implode(', ', $tags->toArray());
    //return implode(', ', $tags->toArray());
  }

  /**
   * Transforms a string (number) to an object (issue).
   *
   * @param  string $tagList
   *
   * @return Tag[]|ArrayCollection|null
   * @throws TransformationFailedException if object (issue) is not found.
   */
  public function reverseTransform($tagList) {
    // no issue number? It's optional, so that's ok
    if (NULL === $tagList) {
      return new ArrayCollection();
    }

    $tagArray = explode(',', $tagList);
    $collection = new ArrayCollection();

    foreach ($tagArray as $tagElem) {
      $tagElem = trim($tagElem);
      $tag = $this->entityManager->getRepository(Tag::class)->findBy(['tagName' => $tagElem]);
      if (NULL === $tag) {
        $tag = new Tag();
        $tag->setName($tagElem);
      }
      $collection->add($tag);
    }

    return $collection;
  }
}