<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * bin/console doctrine:fixtures:load
 *
 * @codeCoverageIgnore
 */
class TagFixtures extends Fixture
{
    public const MIN_TAGS = 50;
    public const MAX_TAGS = 2000;

    public const BATCH_SIZE = 100;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $amount = rand(self::MIN_TAGS, self::MAX_TAGS);
        $existing = [];

        for ($i = 0; $i < $amount; $i++) {
            $tag = new Tag();

            $tagName = null;
            if ($i % 2 == 9) {
                $tagName = $faker->companyEmail;
            } elseif ($i % 2 == 8) {
                $tagName = $faker->firstName;
            } elseif ($i % 2 == 7) {
                $tagName = $faker->lastName;
            } elseif ($i % 5 == 0) {
                $tagName = $faker->city;
            } elseif ($i % 4 == 0) {
                $tagName = $faker->word;
            } elseif ($i % 3 == 0) {
                $tagName = $faker->streetName;
            } elseif ($i % 2 == 0) {
                $tagName = $faker->colorName;
            } else {
                $tagName = $faker->text(rand(5, 10));
            }

            if (\in_array($tagName, $existing)) {
                continue;
            }

            $existing[] = $tagName;
            $tag->setName($tagName);

            $manager->persist($tag);

            if ($i % self::BATCH_SIZE === 0) {
                $manager->flush();
                $manager->clear(Tag::class);
            }
        }
        $manager->flush();
        $manager->clear(Tag::class);
    }
}
