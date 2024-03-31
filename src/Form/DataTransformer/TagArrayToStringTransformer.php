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

/**
 * @implements DataTransformerInterface<array<Tag>, string>
 */
final class TagArrayToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly bool $create
    )
    {
    }

    /**
     * Transforms an array of tags to a string.
     *
     * @param Tag[]|null $value
     */
    public function transform(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        return implode(', ', $value);
    }

    /**
     * Transforms a string to an array of tags.
     *
     * @see \Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer::reverseTransform()
     *
     * @param string|null $value
     * @return Tag[]
     * @throws TransformationFailedException
     */
    public function reverseTransform(mixed $value): mixed
    {
        // check for empty tag list
        if ('' === $value || null === $value) {
            return [];
        }
        $names = array_filter(array_unique(array_map('trim', explode(',', $value))));

        // get the current tags and find the new ones that should be created
        $tags = $this->tagRepository->findBy(['name' => $names]);
        if ($this->create) {
            // works, because of the implicit case: (string) $tag
            $newNames = array_diff($names, $tags);

            foreach ($newNames as $name) {
                $tag = new Tag();
                $tag->setName(mb_substr($name, 0, 100));
                $this->tagRepository->saveTag($tag);

                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
