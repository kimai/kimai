<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * This fixture makes sure that all data is loaded.
 *
 * @codeCoverageIgnore
 */
final class AllFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CustomerFixtures::class,
            TeamFixtures::class,
            TagFixtures::class,
            TimesheetFixtures::class,
            InvoiceFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // this is a fake fixture class, it only exists to make developers life easier
        // if we use the DependentFixtureInterface on the TimesheetFixture directly,
        // we cannot load
        // bin/console doctrine:fixtures:load --append --group=timesheet
        // without executing all dependent fixtures
    }
}
