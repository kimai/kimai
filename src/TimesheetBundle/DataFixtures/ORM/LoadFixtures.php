<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use TimesheetBundle\Entity\Timesheet;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\DataFixtures\ORM\LoadFixtures as AppBundleLoadFixtures;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests. Execute this command to load the data:
 *
 *   $ php bin/console doctrine:fixtures:load
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class LoadFixtures extends AppBundleLoadFixtures
{
    const AMOUNT_ACTIVITIES = 20;
    const AMOUNT_TIMESHEET = 1000;
    const AMOUNT_PROJECTS = 10;
    const AMOUNT_CUSTOMER = 10;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadTimesheet($manager);
    }

    private function loadTimesheet(ObjectManager $manager)
    {
        $amountUsers = count($manager->getRepository(User::class)->findAll());

        for ($i = 0; $i <= self::AMOUNT_TIMESHEET; $i++) {

            $start = new \DateTime();
            $start = $start->modify('- ' . (rand(1, 400)) . ' days');
            $start = $start->modify('- ' . (rand(1, 86400)) . ' seconds');
            $end = clone $start;
            $end = $end->modify('+ '.(rand(1, 43200)).' seconds');

            $entry = new Timesheet();
            $entry->setProjectid(rand(1, self::AMOUNT_PROJECTS));
            $entry->setActivityid(rand(1, self::AMOUNT_ACTIVITIES));
            $entry->setStatusid(1); // TODO
            $entry->setBillable(true);
            $entry->setBudget(0);
            $entry->setCleared(false);
            $entry->setComment($this->getRandomPhrase());
            $entry->setDescription($this->getRandomPhrase());
            $entry->setLocation($this->getRandomLocation());
            $entry->setStart($start->getTimestamp());
            $entry->setEnd($end->getTimestamp());
            $entry->setDuration($end->modify('- ' . $start->getTimestamp() . ' seconds')->getTimestamp());
            $entry->setUserid(rand(1, $amountUsers));
            //$entry->setApproved(false); // TODO
            //$entry->setFixedrate(); // TODO
            //$entry->setRate(); // TODO
            //$entry->setTrackingnumber(); // TODO

            $manager->persist($entry);
        }
        $manager->flush();
    }

    private function getLocations()
    {
        return [
            'Köln',
            'München',
            'New York',
            'Buenos Aires',
            'Hawai',
            'Amsterdam',
            'London',
            'San Francisco',
            'Tokio',
            'Berlin',
            'Sao Paulo',
            'Mexico City',
        ];
    }

    private function getRandomLocation()
    {
        $titles = $this->getLocations();

        return $titles[array_rand($titles)];
    }
}
