<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

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

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * This command is NOT used during runtime and only meant for developers and the CI processes for quality management.
 * This is one of the cases where I don't feel like it is necessary to add tests, so lets "cheat" with:
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:reset:test', description: 'Resets the "test" environment')]
final class ResetTestCommand extends AbstractResetCommand
{
    public function __construct(private EntityManagerInterface $entityManager, string $kernelEnvironment)
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

        $users = [
            // 0=id, 1=hourly rate, 2=Alias, 3=registration date, 4=title, 5=avatar, 6=enabled, 7=password, 8=roles, 9=username, 10=username canonical, 11=email, 12=email canonical, 13=salt, 14=last login, 15=confirmation token, 16=password requested at, 17=api_token
            [
                1,
                53,
                'Clara Haynes',
                '2018-02-06 23:28:57',
                'CFO',
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=monsterid&f=y',
                1,
                '$2y$04$kKBYJ8sKCOhhakCjm9sCp.TQdwLTS1FPkPiWn2KBmaCA7xFL0NA42',
                ['ROLE_CUSTOMER'],
                'clara_customer',
                'clara_customer',
                'clara_customer@example.com',
                'clara_customer@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                2,
                82,
                'John Doe',
                '2018-02-06 23:28:57',
                'Developer',
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y',
                1,
                '$2y$04$36P/xyhP6FbnfFYbXy7V0.ioSe8HjMlJQFYnlIzz2T6Agfi8ob6jK',
                [],
                'john_user',
                'john_user',
                'john_user@example.com',
                'john_user@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                3,
                35,
                'Chris Deactive',
                '2018-02-06 23:28:57',
                'Developer (left company)',
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y',
                0,
                '$2y$04$MLtQBZ9JLzWu1Y01QnNjsuoLm8qC9XRkpUywf6DIbpd9OAL1mEcCi',
                [],
                'chris_user',
                'chris_user',
                'chris_user@example.com',
                'chris_user@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                4,
                35,
                'Tony Maier',
                '2018-02-06 23:28:57',
                'Head of Development',
                'https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg',
                1,
                '$2y$04$rqxiiExfUVzIYRVL2x4JJumQWNPIG6PazXwrSJm/VQFEesR08Uj5i',
                ['ROLE_TEAMLEAD'],
                'tony_teamlead',
                'tony_teamlead',
                'tony_teamlead@example.com',
                'tony_teamlead@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                5,
                81,
                'Anna Smith',
                '2018-02-06 23:28:57',
                'Administrator',
                null,
                1,
                '$2y$04$ct/rVb.naDzYZECnvfTJ2uns/zPHv8.8KcunhTjYFwWQeg1dywI8G',
                ['ROLE_ADMIN'],
                'anna_admin',
                'anna_admin',
                'anna_admin@example.com',
                'anna_admin@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                6,
                46,
                null,
                '2018-02-06 23:28:57',
                'Super Administrator',
                '/bundles/avanzuadmintheme/img/avatar.png',
                1,
                '$2y$04$kuhEEPw/CBMYc3x7SOv27eC1hQSmrtFvgJI2ULRuJeddAVDyrPKJ2',
                ['ROLE_SUPER_ADMIN'],
                'susan_super',
                'susan_super',
                'susan_super@example.com',
                'susan_super@example.com',
                null,
                '2020-04-14 09:50:38',
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                7,
                null,
                'Test User 1',
                null,
                'Quality Tester 1',
                null,
                1,
                '$2y$04$kuhEEPw/CBMYc3x7SOv27eC1hQSmrtFvgJI2ULRuJeddAVDyrPKJ2',
                [],
                'test_user_1',
                'test_user_1',
                'test_user_1@example.com',
                'test_user_1@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
            [
                8,
                null,
                'Test User 2',
                null,
                'Quality Tester 2',
                null,
                1,
                '$2y$04$kuhEEPw/CBMYc3x7SOv27eC1hQSmrtFvgJI2ULRuJeddAVDyrPKJ2',
                [],
                'test_user_2',
                'test_user_2',
                'test_user_2@example.com',
                'test_user_2@example.com',
                null,
                null,
                null,
                null,
                '$2y$13$X8/msijlFUgvRaiGLCJP/ep2hRyjpd.TSNz3cuutZLp05FpuBsYfO'
            ],
        ];

        $userEntities = [];
        foreach ($users as $userConf) {
            $user = new User();
            foreach (User::WIZARDS as $wizard) {
                $user->setWizardAsSeen($wizard);
            }
            if ($userConf[1] !== null) {
                $user->setPreferenceValue(UserPreference::HOURLY_RATE, $userConf[1]);
            }
            if ($userConf[2] !== null) {
                $user->setAlias($userConf[2]);
            }
            if ($userConf[3] !== null) {
                $user->setRegisteredAt(new \DateTime($userConf[3]));
            }
            if ($userConf[4] !== null) {
                $user->setTitle($userConf[4]);
            }
            if ($userConf[5] !== null) {
                $user->setAvatar($userConf[5]);
            }
            if ($userConf[6] !== null) {
                $user->setEnabled((bool) $userConf[6]);
            }
            $user->setPassword($userConf[7]);
            if ($userConf[8] !== null && !empty($userConf[8])) {
                $user->setRoles($userConf[8]);
            } else {
                $user->setRoles(['ROLE_USER']);
            }
            $user->setUserIdentifier($userConf[9]);
            if ($userConf[10] !== null) {
                // removed field: UsernameCanonical
            }
            if ($userConf[11] !== null) {
                $user->setEmail($userConf[11]);
            }
            if ($userConf[12] !== null) {
                // removed field: EmailCanonical
            }
            if ($userConf[17] !== null) {
                $user->setApiToken($userConf[17]);
            }

            $this->entityManager->persist($user);
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
