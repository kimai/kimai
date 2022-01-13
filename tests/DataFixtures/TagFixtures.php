<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Tag;
use Doctrine\Persistence\ObjectManager;

/**
 * Defines the sample data to load in during controller tests.
 */
final class TagFixtures implements TestFixture
{
    /**
     * @var string[]
     */
    private $tagArray = [];
    /**
     * @var callable
     */
    private $callback;

    /**
     * Will be called prior to persisting the object.
     *
     * @param callable $callback
     * @return TagFixtures
     */
    public function setCallback(callable $callback): TagFixtures
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTagArray(): array
    {
        return $this->tagArray;
    }

    /**
     * @param string[] $tagArray
     * @return TagFixtures
     */
    public function setTagArray(array $tagArray): TagFixtures
    {
        $this->tagArray = $tagArray;

        return $this;
    }

    public function importAmount(int $amount): void
    {
        $tags = [];
        for ($i = 0; $i <= $amount; $i++) {
            $tags[] = (string) $i;
        }
        $this->setTagArray($tags);
    }

    /**
     * @param ObjectManager $manager
     * @return Tag[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];

        foreach ($this->getTagArray() as $tagName) {
            $tag = $this->createTagEntry($tagName);

            if (null !== $this->callback) {
                \call_user_func($this->callback, $tag);
            }
            $manager->persist($tag);
            $created[] = $tag;
        }
        $manager->flush();

        return $created;
    }

    private function createTagEntry(string $tagName): Tag
    {
        $tagObject = new Tag();
        $tagObject->setName($tagName);

        return $tagObject;
    }
}
