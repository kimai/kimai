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
     * @param array<string>|string|null $value
     * @return Tag[]
     * @throws TransformationFailedException
     */
    public function reverseTransform(mixed $value): array
    {
        // check for empty tag list
        if ('' === $value || null === $value) {
            return [];
        }
        if (!\is_array($value)) {
            $names = array_filter(array_unique(array_map('trim', explode(',', $value))));
        } else {
            $names = $value;
        }

        $tags = [];
        foreach ($names as $tagName) {
            if ($tagName === null || $tagName === '') {
                continue;
            }

            $tagName = trim($tagName);
            $tag = null;

            if (is_numeric($tagName)) {
                $tag = $this->tagRepository->find($tagName);
            }

            if ($tag === null) {
                $tag = $this->tagRepository->findTagByName($tagName);
            }

            // get the current tags and find the new ones that should be created
            if ($this->create && $tag === null) {
                $tag = new Tag();
                $tag->setName(mb_substr($tagName, 0, 100));
                $this->tagRepository->saveTag($tag);
            }

            if ($tag !== null) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
