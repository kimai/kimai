<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 2019-01-07
 * Time: 07:14
 */

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transformationsklasse für die Verarbeitung von Tag-Objekten zu einer Liste von Strings
 *
 * @package App\Form\DataTransformer
 */
class TagArrayToStringTransformer implements DataTransformerInterface {

  /** @var TagRepository */
  private $tagRepository;

  /**
   * Konstruktor
   *
   * @param TagRepository $tagRepository
   */
  public function __construct(TagRepository $tagRepository) {
    dump($tagRepository);
    $this->tagRepository = $tagRepository;
  }

  /**
   * Transforms an object (issue) to a string (number).
   *
   * @param  Tag[]|null $tags
   *
   * @return string
   */
  public function transform($tags): string {
    if (NULL === $tags || sizeof($tags) < 1) {
      return '';
    }

    return implode(', ', $tags);
  }

  /**
   * Transforms a string to an array of tags.
   *
   * @param  string $stringOfTags
   *
   * @return Tag[]
   * @throws TransformationFailedException if object (issue) is not found.
   */
  public function reverseTransform($stringOfTags): array {
    // check for empty tag list
    if (NULL === $stringOfTags || '' === $stringOfTags) {
      return [];
    }

    $names = array_filter(array_unique(array_map('trim', explode(',', $stringOfTags))));

    // Get the current tags and find the new ones that should be created.
    $tags = $this->tagRepository->findBy([
        'tagName' => $names,
    ]);
    $newNames = array_diff($names, $tags);
    foreach ($newNames as $name) {
      $tag = new Tag();
      $tag->setName($name);
      $tags[] = $tag;

      // There's no need to persist these new tags because Doctrine does that automatically
      // thanks to the cascade={"persist"} option in the App\Entity\Timesheet::$tags property.
    }

    // Return an array of tags to transform them back into a Doctrine Collection.
    // See Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer::reverseTransform()
    return $tags;
  }
}