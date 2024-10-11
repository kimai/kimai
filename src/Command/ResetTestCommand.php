<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\DataFixtures\UserFixtures;
use App\Entity\AccessToken;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * This command is NOT used during runtime and only meant for developers and the CI processes for quality management.
 * This is one of the cases where it is necessary to add tests:
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:reset:test', description: 'Resets the "test" environment')]
final class ResetTestCommand extends AbstractResetCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        string $kernelEnvironment
    )
    {
        parent::__construct($kernelEnvironment);
    }

    protected function loadData(InputInterface $input, OutputInterface $output): void
    {
        $activity = new Activity();
        $activity->setName('Test');
        $activity->setComment('Test comment');
        $activity->setVisible(true);
        $activity->setTimeBudget(100000);
        $activity->setBudget(1000);
        $this->entityManager->persist($activity);

        $customer = new Customer('Test');
        $customer->setNumber('1');
        $customer->setComment('Test comment');
        $customer->setContact('Test');
        $customer->setAddress('Test');
        $customer->setCompany('Test');
        $customer->setCountry('DE');
        $customer->setCurrency('EUR');
        $customer->setPhone('111');
        $customer->setFax('222');
        $customer->setMobile('333');
        $customer->setEmail('test@example.com');
        $customer->setTimeBudget(100000);
        $customer->setBudget(1000);
        $customer->setTimezone('Europe/Berlin');
        $this->entityManager->persist($customer);

        $project = new Project();
        $project->setComment('Test comment');
        $project->setName('Test');
        $project->setOrderNumber('111');
        $project->setTimeBudget(100000);
        $project->setBudget(1000);
        $project->setCustomer($customer);
        $this->entityManager->persist($project);

        $user1 = new User();
        $user1->setPreferenceValue(UserPreference::HOURLY_RATE, 53);
        $user1->setAlias('Clara Haynes');
        $user1->setRegisteredAt(new \DateTime('2018-02-06 23:28:57'));
        $user1->setTitle('CFO');
        $user1->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=monsterid&f=y');
        $user1->setEnabled(true);
        $user1->setRoles(['ROLE_CUSTOMER']);
        $user1->setUserIdentifier('clara_customer');
        $user1->setEmail('clara_customer@example.com');
        $token1 = new AccessToken($user1, UserFixtures::DEFAULT_API_TOKEN . '_customer');
        $token1->setName('Test fixture');

        $user2 = new User();
        $user2->setPreferenceValue(UserPreference::HOURLY_RATE, 82);
        $user2->setAlias('John Doe');
        $user2->setRegisteredAt(new \DateTime('2018-02-06 23:28:57'));
        $user2->setTitle('Developer');
        $user2->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y');
        $user2->setEnabled(true);
        $user2->setRoles(['ROLE_USER']);
        $user2->setUserIdentifier('john_user');
        $user2->setEmail('john_user@example.com');
        $token2 = new AccessToken($user2, UserFixtures::DEFAULT_API_TOKEN . '_user');
        $token2->setName('Test fixture');

        $user3 = new User();
        $user3->setPreferenceValue(UserPreference::HOURLY_RATE, 35);
        $user3->setAlias('Chris Deactive');
        $user3->setRegisteredAt(new \DateTime('2018-02-06 23:28:57'));
        $user3->setTitle('Developer (left company)');
        $user3->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y');
        $user3->setEnabled(false);
        $user3->setRoles(['ROLE_USER']);
        $user3->setUserIdentifier('chris_user');
        $user3->setEmail('chris_user@example.com');
        $token3 = new AccessToken($user3, UserFixtures::DEFAULT_API_TOKEN . '_inactive');
        $token3->setName('Test fixture');

        $user4 = new User();
        $user4->setPreferenceValue(UserPreference::HOURLY_RATE, 35);
        $user4->setAlias('Tony Maier');
        $user4->setRegisteredAt(new \DateTime('2018-02-06 23:28:57'));
        $user4->setTitle('Head of Development');
        $user4->setAvatar('https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg');
        $user4->setEnabled(true);
        $user4->setRoles(['ROLE_TEAMLEAD']);
        $user4->setUserIdentifier('tony_teamlead');
        $user4->setEmail('tony_teamlead@example.com');
        $token4 = new AccessToken($user4, UserFixtures::DEFAULT_API_TOKEN . '_teamlead');
        $token4->setName('Test fixture');

        $user5 = new User();
        $user5->setPreferenceValue(UserPreference::HOURLY_RATE, 81);
        $user5->setAlias('Anna Smith');
        $user5->setRegisteredAt(new \DateTime('2018-02-06 23:28:57'));
        $user5->setTitle('Administrator');
        $user5->setEnabled(true);
        $user5->setRoles(['ROLE_ADMIN']);
        $user5->setUserIdentifier('anna_admin');
        $user5->setEmail('anna_admin@example.com');
        $token5 = new AccessToken($user5, UserFixtures::DEFAULT_API_TOKEN . '_admin');
        $token5->setName('Test fixture');

        $user6 = new User();
        $user6->setPreferenceValue(UserPreference::HOURLY_RATE, 46);
        $user6->setRegisteredAt(new \DateTime('2018-02-06 23:28:57'));
        $user6->setTitle('Super Administrator');
        $user6->setAvatar('/bundles/avanzuadmintheme/img/avatar.png');
        $user6->setEnabled(true);
        $user6->setRoles(['ROLE_SUPER_ADMIN']);
        $user6->setUserIdentifier('susan_super');
        $user6->setEmail('susan_super@example.com');
        $token6 = new AccessToken($user6, UserFixtures::DEFAULT_API_TOKEN . '_super');
        $token6->setName('Test fixture');

        $user7 = new User();
        $user7->setAlias('Test User 1');
        $user7->setTitle('Quality Tester 1');
        $user7->setEnabled(true);
        $user7->setRoles(['ROLE_USER']);
        $user7->setUserIdentifier('test_user_1');
        $user7->setEmail('test_user_1@example.com');
        $token7 = new AccessToken($user7, UserFixtures::DEFAULT_API_TOKEN . '_qa1');
        $token7->setName('Test fixture');

        $user8 = new User();
        $user8->setAlias('Test User 2');
        $user8->setTitle('Quality Tester 2');
        $user8->setEnabled(true);
        $user8->setRoles(['ROLE_USER']);
        $user8->setUserIdentifier('test_user_2');
        $user8->setEmail('test_user_2@example.com');
        $token8 = new AccessToken($user8, UserFixtures::DEFAULT_API_TOKEN . '_qa2');
        $token8->setName('Test fixture');

        /** @var array<int, array{0: User, 1: AccessToken}> $users */
        $users = [
            [$user1, $token1],
            [$user2, $token2],
            [$user3, $token3],
            [$user4, $token4],
            [$user5, $token5],
            [$user6, $token6],
            [$user7, $token7],
            [$user8, $token8],
        ];

        $userEntities = [];
        foreach ($users as $items) {
            $user = $items[0];
            $user->setPassword($this->passwordHasher->hashPassword($user, UserFixtures::DEFAULT_PASSWORD));
            $user->setApiToken($this->passwordHasher->hashPassword($user, UserFixtures::DEFAULT_API_TOKEN));
            foreach (User::WIZARDS as $wizard) {
                $user->setWizardAsSeen($wizard);
            }
            $this->entityManager->persist($user);

            $accessToken = $items[1];
            $this->entityManager->persist($accessToken);

            $userEntities[] = $user;
        }

        $team = new Team('Test team');
        $team->addTeamlead($userEntities[6]);
        $team->addUser($userEntities[7]);
        $this->entityManager->persist($team);

        $this->entityManager->flush();
    }

    protected function dropSchema(SymfonyStyle $io, OutputInterface $output): int
    {
        try {
            $command = $this->getApplication()->find('doctrine:schema:drop');
            $command->run(new ArrayInput(['--force' => true, '--full-database' => true]), $output);
        } catch (Exception $ex) {
            $io->error('Failed to drop database schema: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
