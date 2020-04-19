<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateUserCommand extends Command
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var ManagerRegistry
     */
    private $doctrine;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(UserPasswordEncoderInterface $encoder, ManagerRegistry $registry, ValidatorInterface $validator)
    {
        $this->encoder = $encoder;
        $this->doctrine = $registry;
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $roles = implode(',', [User::DEFAULT_ROLE, User::ROLE_ADMIN]);

        $this
            ->setName('kimai:create-user')
            ->setDescription('Create a new user')
            ->setHelp('This command allows you to create a new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'A name for the new user (must be unique)')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address of the new user (must be unique)')
            ->addArgument(
                'role',
                InputArgument::OPTIONAL,
                'A comma separated list of user roles, e.g. "' . $roles . '"',
                User::DEFAULT_ROLE
            )
            ->addArgument('password', InputArgument::OPTIONAL, 'Password for the new user (requested if not provided)')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        if (null !== $input->getArgument('password')) {
            $password = $input->getArgument('password');
        } else {
            $password = $this->askForPassword($input, $output);
        }

        $role = $role ?: User::DEFAULT_ROLE;

        $user = new User();
        $user->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setEnabled(true)
            ->setRoles(explode(',', $role))
        ;

        $errors = $this->validator->validate($user, null, ['Registration']);
        if ($errors->count() > 0) {
            /** @var \Symfony\Component\Validator\ConstraintViolation $error */
            foreach ($errors as $error) {
                $value = $error->getInvalidValue();
                $io->error(
                    $error->getPropertyPath()
                    . ' (' . (\is_array($value) ? implode(',', $value) : $value) . ')'
                    . "\n    "
                    . $error->getMessage()
                );
            }

            return 1;
        }

        try {
            $pwd = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($pwd);

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $io->success('Success! Created user: ' . $user->getUsername());
        } catch (\Exception $ex) {
            $io->error('Failed to create user: ' . $user->getUsername());
            $io->error('Reason: ' . $ex->getMessage());

            return 2;
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        /* @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $passwordQuestion = new Question('Please enter the password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function (?string $value) {
            $password = trim($value);
            if (empty($password)) {
                throw new \Exception('The password may not be empty');
            }

            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);

        return $helper->ask($input, $output, $passwordQuestion);
    }
}
