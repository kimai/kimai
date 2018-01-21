<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class AppFixtures extends Fixture
{
    use FixturesTrait;

    const DEFAULT_PASSWORD = 'kitten';

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * AppFixtures constructor.
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadUsers(ObjectManager $manager)
    {
        $passwordEncoder = $this->encoder;

        foreach ($this->getUserDefinition() as $userData) {
            $user = new User();
            $user
                ->setAlias($userData[0])
                ->setTitle($userData[1])
                ->setUsername($userData[2])
                ->setEmail($userData[3])
                ->setRoles([$userData[4]])
                ->setAvatar($userData[5])
                ->setActive($userData[6])
                ->setPassword($passwordEncoder->encodePassword($user, self::DEFAULT_PASSWORD))
            ;

            $preference = new UserPreference();
            $preference->setName(UserPreference::HOURLY_RATE);
            $preference->setValue(rand(0, 100));
            $preference->setUser($user);
            $user->setPreferences([$preference]);

            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @return []
     */
    protected function getUserDefinition()
    {
        return [
            ['Clara Haynes', 'CFO', 'clara_customer', 'clara_customer@example.com', 'ROLE_CUSTOMER', 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=monsterid&f=y', true],
            ['John Doe', 'Developer', 'john_user', 'john_user@example.com', 'ROLE_USER', 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y', true],
            // inactive user to test login
            ['Chris Deactive', 'Developer (left company)', 'chris_user', 'chris_user@example.com', 'ROLE_USER', 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y', false],
            ['Tony Maier', 'Head of Development', 'tony_teamlead', 'tony_teamlead@example.com', 'ROLE_TEAMLEAD', 'https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg', true],
            // no avatar to test default image macro
            ['Anna Smith', 'Administrator', 'anna_admin', 'anna_admin@example.com', 'ROLE_ADMIN', null, true],
            // no alias to test twig username macro
            [null, 'Super Administrator', 'susan_super', 'susan_super@example.com', 'ROLE_SUPER_ADMIN', '/bundles/avanzuadmintheme/img/avatar.png', true]
        ];
    }
}
