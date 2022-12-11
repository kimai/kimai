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

final class TagArrayToStringTransformer implements DataTransformerInterface
{
    public function __construct(private TagRepository $tagRepository)
    {
    }

    /**
     * Transforms an array of tags to a string.
     *
     * @param Tag[]|null $tags
     * @return string
     */
    public function transform(mixed $tags): mixed
    {
        if (empty($tags)) {
            return '';
        }

        return implode(', ', $tags);
    }

    /**
     * Transforms a string to an array of tags.
     *
     * @see \Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer::reverseTransform()
     *
     * @param string|null $stringOfTags
     * @return Tag[]
     * @throws TransformationFailedException
     */
    public function reverseTransform(mixed $stringOfTags): mixed
    {
        // check for empty tag list
        if ('' === $stringOfTags || null === $stringOfTags) {
            return [];
        }

        $names = array_filter(array_unique(array_map('trim', explode(',', $stringOfTags))));

        // get the current tags and find the new ones that should be created
        $tags = $this->tagRepository->findBy(['name' => $names]);
        // works, because of the implicit case: (string) $tag
        $newNames = array_diff($names, $tags);

        foreach ($newNames as $name) {
            $tag = new Tag();
            $tag->setName($name);
            $tags[] = $tag;

            // new tags persist automatically thanks to the cascade={"persist"}
        }

        return $tags;
    }
}
