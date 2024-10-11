<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 *
 * @codeCoverageIgnore
 */
final class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public const DEFAULT_PASSWORD = 'password';
    public const DEFAULT_API_TOKEN = 'token';
    public const DEFAULT_AVATAR = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y';

    public const USERNAME_USER = 'john_user';
    public const USERNAME_TEAMLEAD = 'tony_teamlead';
    public const USERNAME_ADMIN = 'anna_admin';
    public const USERNAME_SUPER_ADMIN = 'susan_super';

    public const AMOUNT_EXTRA_USER = 25;

    public const MIN_RATE = 30;
    public const MAX_RATE = 120;

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function getGroups(): array
    {
        return ['user'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadDefaultAccounts($manager);
        $this->loadTestUsers($manager);
    }

    /**
     * Default users for all test cases
     */
    private function loadDefaultAccounts(ObjectManager $manager): void
    {
        $allUsers = $this->getUserDefinition();
        foreach ($allUsers as $userData) {
            $manager->persist($userData[0]);
            $manager->persist($userData[1]);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param User $user
     * @param string|null $timezone
     * @return array<UserPreference>
     */
    private function getUserPreferences(User $user, string $timezone = null): array
    {
        $preferences = [];

        $prefHourlyRate = new UserPreference(UserPreference::HOURLY_RATE, rand(self::MIN_RATE, self::MAX_RATE));
        $user->addPreference($prefHourlyRate);
        $preferences[] = $prefHourlyRate;

        if (null !== $timezone) {
            $prefTimezone = new UserPreference(UserPreference::TIMEZONE, $timezone);
            $user->addPreference($prefTimezone);
            $preferences[] = $prefTimezone;
        }

        return $preferences;
    }

    /**
     * Generate randomized test users, without API access.
     */
    private function loadTestUsers(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $existingName = [];
        $existingEmail = [];

        for ($i = 1; $i <= self::AMOUNT_EXTRA_USER; $i++) {
            $username = $faker->userName();
            $email = $faker->email();

            if (\in_array($username, $existingName)) {
                continue;
            }

            if (\in_array($email, $existingEmail)) {
                continue;
            }

            $existingName[] = $username;
            $existingEmail[] = $email;

            $user = new User();
            $user->setAlias($faker->name());
            $user->setTitle(substr($faker->jobTitle(), 0, 49));
            $user->setUserIdentifier($username);
            $user->setEmail($email);
            $user->setRoles([User::ROLE_USER]);
            $user->setEnabled(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
            $manager->persist($user);

            $prefs = $this->getUserPreferences($user);
            $user->setPreferences($prefs);
            $manager->persist($prefs[0]);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * Do NOT set wizard as seen here, because they should be visible in the demo installation.
     *
     * @return array<int, array{0: User, 1:  AccessToken}>
     */
    private function getUserDefinition(): array
    {
        $all = [];

        $user = new User();
        $user->setAlias('John Doe');
        $user->setTitle('Developer');
        $user->setUserIdentifier(self::USERNAME_USER);
        $user->setEmail('john_user@example.com');
        $user->setRoles([User::ROLE_USER]);
        $user->setAvatar(self::DEFAULT_AVATAR);
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'America/Vancouver');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_john');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        $user = new User();
        $user->setAlias('John Doe');
        $user->setTitle('Developer');
        $user->setUserIdentifier('user');
        $user->setEmail('user@example.com');
        $user->setRoles([User::ROLE_USER]);
        $user->setAvatar(self::DEFAULT_AVATAR);
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'America/Vancouver');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_user');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        // inactive user to test login
        $user = new User();
        $user->setAlias('Chris Deactive');
        $user->setTitle('Developer (left company)');
        $user->setUserIdentifier('chris_user');
        $user->setEmail('chris_user@example.com');
        $user->setRoles([User::ROLE_USER]);
        $user->setAvatar(self::DEFAULT_AVATAR);
        $user->setEnabled(false);
        $prefs = $this->getUserPreferences($user, 'Australia/Sydney');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_inactive');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        $user = new User();
        $user->setAlias('Tony Maier');
        $user->setTitle('Head of Sales');
        $user->setUserIdentifier(self::USERNAME_TEAMLEAD);
        $user->setEmail('tony_teamlead@example.com');
        $user->setRoles([User::ROLE_TEAMLEAD]);
        $user->setAvatar('https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg');
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'Asia/Bangkok');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_teamlead');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        $user = new User();
        $user->setAlias('Tony Maier');
        $user->setTitle('Head of Sales');
        $user->setUserIdentifier('teamlead');
        $user->setEmail('teamlead@example.com');
        $user->setRoles([User::ROLE_TEAMLEAD]);
        $user->setAvatar('https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg');
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'Asia/Bangkok');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_tony');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        // no avatar to test default image macro
        $user = new User();
        $user->setAlias('Anna Smith');
        $user->setTitle('Administrator');
        $user->setUserIdentifier(self::USERNAME_ADMIN);
        $user->setEmail('anna_admin@example.com');
        $user->setRoles([User::ROLE_ADMIN]);
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'Europe/London');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_anna');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        $user = new User();
        $user->setAlias('Anna Smith');
        $user->setTitle('Administrator');
        $user->setUserIdentifier('administrator');
        $user->setEmail('administrator@example.com');
        $user->setRoles([User::ROLE_ADMIN]);
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'Europe/London');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_admin');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        // no alias to test twig username macro
        $user = new User();
        $user->setTitle('Super Administrator');
        $user->setUserIdentifier(self::USERNAME_SUPER_ADMIN);
        $user->setEmail('susan_super@example.com');
        $user->setRoles([User::ROLE_SUPER_ADMIN]);
        $user->setAvatar('/touch-icon-192x192.png');
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'Europe/Berlin');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_susan');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        $user = new User();
        $user->setTitle('Super Administrator');
        $user->setUserIdentifier('super_admin');
        $user->setEmail('super_admin@example.com');
        $user->setRoles([User::ROLE_SUPER_ADMIN]);
        $user->setAvatar('/touch-icon-192x192.png');
        $user->setEnabled(true);
        $prefs = $this->getUserPreferences($user, 'Europe/Berlin');
        $user->setPreferences($prefs);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
        $user->setApiToken($this->passwordHasher->hashPassword($user, self::DEFAULT_API_TOKEN));
        $token = new AccessToken($user, self::DEFAULT_API_TOKEN . '_super');
        $token->setName('User fixture');
        $all[] = [$user, $token];

        return $all;
    }
}
