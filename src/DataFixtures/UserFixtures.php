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
 *
 * @codeCoverageIgnore
 */
class UserFixtures extends Fixture
{
    public const DEFAULT_PASSWORD = 'kitten';
    public const DEFAULT_API_TOKEN = 'api_kitten';
    public const DEFAULT_AVATAR = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y';

    public const USERNAME_USER = 'john_user';
    public const USERNAME_TEAMLEAD = 'tony_teamlead';
    public const USERNAME_ADMIN = 'anna_admin';
    public const USERNAME_SUPER_ADMIN = 'susan_super';

    public const AMOUNT_EXTRA_USER = 25;

    public const MIN_RATE = 30;
    public const MAX_RATE = 120;

    // lower batch size, as user preferences are added in the same run
    public const BATCH_SIZE = 50;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
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
        $this->loadDefaultAccounts($manager);
        $this->loadTestUsers($manager);
    }

    /**
     * Default users for all test cases
     *
     * @param ObjectManager $manager
     */
    private function loadDefaultAccounts(ObjectManager $manager)
    {
        $passwordEncoder = $this->encoder;

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
                ->setApiToken($passwordEncoder->encodePassword($user, self::DEFAULT_API_TOKEN))
                ->setPreferences($this->getUserPreferences($user, $userData[7]))
            ;

            $manager->persist($user);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param User $user
     * @param string|null $timezone
     * @return array
     */
    private function getUserPreferences(User $user, string $timezone = null)
    {
        $preferences = [];

        $prefHourlyRate = new UserPreference();
        $prefHourlyRate->setName(UserPreference::HOURLY_RATE);
        $prefHourlyRate->setValue(rand(self::MIN_RATE, self::MAX_RATE));
        $prefHourlyRate->setUser($user);
        $preferences[] = $prefHourlyRate;

        if (null !== $timezone) {
            $prefTimezone = new UserPreference();
            $prefTimezone->setName(UserPreference::TIMEZONE);
            $prefTimezone->setValue($timezone);
            $prefTimezone->setUser($user);
            $preferences[] = $prefTimezone;
        }

        return $preferences;
    }

    /**
     * Generate randomized test users, which don't have API access.
     *
     * @param ObjectManager $manager
     */
    private function loadTestUsers(ObjectManager $manager)
    {
        $passwordEncoder = $this->encoder;
        $faker = Factory::create();
        $existingName = [];
        $existingEmail = [];

        for ($i = 1; $i <= self::AMOUNT_EXTRA_USER; $i++) {
            $username = $faker->userName;
            $email = $faker->email;

            if (\in_array($username, $existingName)) {
                continue;
            }

            if (\in_array($email, $existingEmail)) {
                continue;
            }

            $existingName[] = $username;
            $existingEmail[] = $email;

            $user = new User();
            $user
                ->setAlias($faker->name)
                ->setTitle(substr($faker->jobTitle, 0, 49))
                ->setUsername($username)
                ->setEmail($email)
                ->setRoles([User::ROLE_USER])
                ->setEnabled(true)
                ->setPassword($passwordEncoder->encodePassword($user, self::DEFAULT_PASSWORD))
                ->setPreferences($this->getUserPreferences($user))
            ;

            $manager->persist($user);

            if ($i % self::BATCH_SIZE === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @return array
     */
    protected function getUserDefinition()
    {
        // alias = $userData[0]
        // title = $userData[1]
        // username = $userData[2]
        // email = $userData[3]
        // roles = [$userData[4]]
        // avatar = $userData[5]
        // enabled = $userData[6]
        // timezone = $userData[7]

        return [
            [
                'John Doe',
                'Developer',
                self::USERNAME_USER,
                'john_user@example.com',
                User::ROLE_USER,
                self::DEFAULT_AVATAR,
                true,
                'America/Vancouver',
            ],
            // inactive user to test login
            [
                'Chris Deactive',
                'Developer (left company)',
                'chris_user',
                'chris_user@example.com',
                User::ROLE_USER,
                self::DEFAULT_AVATAR,
                false,
                'Australia/Sydney',
            ],
            [
                'Tony Maier',
                'Head of Sales',
                self::USERNAME_TEAMLEAD,
                'tony_teamlead@example.com',
                User::ROLE_TEAMLEAD,
                'https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg',
                true,
                'Asia/Bangkok',
            ],
            // no avatar to test default image macro
            [
                'Anna Smith',
                'Administrator',
                self::USERNAME_ADMIN,
                'anna_admin@example.com',
                User::ROLE_ADMIN,
                null,
                true,
                'Europe/London',
            ],
            // no alias to test twig username macro
            [
                null,
                'Super Administrator',
                self::USERNAME_SUPER_ADMIN,
                'susan_super@example.com',
                User::ROLE_SUPER_ADMIN,
                '/build/images/default_avatar.png',
                true,
                'Europe/Berlin',
            ]
        ];
    }
}
