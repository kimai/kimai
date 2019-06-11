<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TagArrayToStringTransformer implements DataTransformerInterface
{
    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @param TagRepository $tagRepository
     */
    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Transforms an array of tags to a string.
     *
     * @param Tag[]|null $tags
     *
     * @return string
     */
    public function transform($tags)
    {
        if (empty($tags)) {
            return '';
        }

        return implode(', ', $tags);
    }

    /**
     * Transforms a string to an array of tags.
     *
     * @param string $stringOfTags
     *
     * @return Tag[]
     * @throws TransformationFailedException if object (issue) is not found
     */
    public function reverseTransform($stringOfTags)
    {
        // check for empty tag list
        if (empty($stringOfTags)) {
            return [];
        }

        $names = array_filter(array_unique(array_map('trim', explode(',', $stringOfTags))));

        // Get the current tags and find the new ones that should be created
        $tags = $this->tagRepository->findBy(['name' => $names]);

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
