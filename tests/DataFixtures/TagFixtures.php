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
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Defines the sample data to load in during controller tests.
 */
class TagFixtures extends Fixture
{
    /**
     * @var string[]
     */
    protected $tagArray = [];

    /**
     * @return string[]
     */
    public function getTagArray()
    {
        return $this->tagArray;
    }

    /**
     * @param string[] $tagArray
     * @return TagFixtures
     */
    public function setTagArray(array $tagArray)
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
            $entry = $this->createTagEntry($tagName);
            $manager->persist($entry);
        }
        $manager->flush();
    }

    protected function createTagEntry(string $tagName): Tag
    {
        $tagObject = new Tag();
        $tagObject->setName($tagName);

        return $tagObject;
    }
}
