<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public const DEFAULT_PASSWORD = 'kitten';
    public const DEFAULT_AVATAR = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y';

    public const USERNAME_USER = 'john_user';
    public const USERNAME_TEAMLEAD = 'tony_teamlead';
    public const USERNAME_ADMIN = 'anna_admin';
    public const USERNAME_SUPER_ADMIN = 'susan_super';

    public const AMOUNT_EXTRA_USER = 50;

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

        // default users for all test cases
        $allUsers = $this->getUserDefinition();
        foreach ($allUsers as $userData) {
            $user = new User();
            $user
                ->setAlias($userData[0])
                ->setTitle($userData[1])
                ->setUsername($userData[2])
                ->setEmail($userData[3])
                ->setRoles([$userData[4]])
                ->setAvatar($userData[5])
                ->setEnabled($userData[6])
                ->setPassword($passwordEncoder->encodePassword($user, self::DEFAULT_PASSWORD))
            ;

            $preference = new UserPreference();
            $preference->setName(UserPreference::HOURLY_RATE);
            $preference->setValue(rand(0, 100));
            $preference->setUser($user);
            $user->setPreferences([$preference]);

            $manager->persist($user);
        }

        // randomized test users
        $faker = Factory::create();
        for($i = 0; $i < self::AMOUNT_EXTRA_USER; $i++) {
            $user = new User();
            $user
                ->setAlias($faker->name)
                ->setTitle(substr($faker->jobTitle, 0, 159))
                ->setUsername($faker->userName)
                ->setEmail($faker->email)
                ->setRoles([User::ROLE_USER])
                ->setAvatar(self::DEFAULT_AVATAR)
                ->setEnabled(true)
                ->setPassword($passwordEncoder->encodePassword($user, self::DEFAULT_PASSWORD))
            ;

            $preference = new UserPreference();
            $preference->setName(UserPreference::HOURLY_RATE);
            $preference->setValue(rand(0, 100));
            $preference->setUser($user);
            $user->setPreferences([$preference]);

            if ($i % 9 == 0) {
                $manager->flush();
            }

            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getUserDefinition()
    {
        return [
            [
                'Clara Haynes', 'CFO', 'clara_customer', 'clara_customer@example.com', User::ROLE_CUSTOMER,
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=monsterid&f=y', true
            ],
            [
                'John Doe', 'Developer', self::USERNAME_USER, 'john_user@example.com', User::ROLE_USER,
                self::DEFAULT_AVATAR, true
            ],
            // inactive user to test login
            [
                'Chris Deactive', 'Developer (left company)', 'chris_user', 'chris_user@example.com', User::ROLE_USER,
                self::DEFAULT_AVATAR, false
            ],
            [
                'Tony Maier', 'Head of Sales', self::USERNAME_TEAMLEAD, 'tony_teamlead@example.com', User::ROLE_TEAMLEAD,
                'https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg', true
            ],
            // no avatar to test default image macro
            [
                'Anna Smith', 'Administrator', self::USERNAME_ADMIN, 'anna_admin@example.com', User::ROLE_ADMIN, null, true
            ],
            // no alias to test twig username macro
            [
                null, 'Super Administrator', self::USERNAME_SUPER_ADMIN, 'susan_super@example.com', User::ROLE_SUPER_ADMIN,
                '/build/images/default_avatar.png', true
            ]
        ];
    }
}
