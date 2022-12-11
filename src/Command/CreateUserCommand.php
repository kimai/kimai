<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\User;
use App\User\UserService;
use App\Validator\ValidationFailedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'kimai:user:create')]
final class CreateUserCommand extends AbstractUserCommand
{
    public function __construct(private UserService $userService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $roles = implode(',', [User::DEFAULT_ROLE, User::ROLE_ADMIN]);

        $this
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

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        $user = $this->userService->createNewUser();
        $user->setUserIdentifier($username);
        $user->setPlainPassword($password);
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setRoles(explode(',', $role));

        try {
            $this->userService->saveNewUser($user);
            $io->success(sprintf('Success! Created user: %s', $username));
        } catch (ValidationFailedException $ex) {
            $this->validationError($ex, $io);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
