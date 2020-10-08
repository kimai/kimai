<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Defines the sample data to load in during controller tests.
 */
final class TagFixtures extends Fixture
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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getTagArray() as $tagName) {
            $tag = $this->createTagEntry($tagName);

            if (null !== $this->callback) {
                \call_user_func($this->callback, $tag);
            }
            $manager->persist($tag);
        }
        $manager->flush();
    }

    private function createTagEntry(string $tagName): Tag
    {
        $tagObject = new Tag();
        $tagObject->setName($tagName);

        return $tagObject;
    }
}
