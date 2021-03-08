<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use Doctrine\Persistence\ObjectManager;

/**
 * Defines the sample data to load in during controller tests.
 */
interface TestFixture
{
    /**
     * Load data fixtures with the passed EntityManager and returns the created objects.
     */
    public function load(ObjectManager $manager): array;
}
