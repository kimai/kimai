<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\User\UserService;
use App\Validator\ValidationFailedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'kimai:user:password')]
final class ChangePasswordCommand extends AbstractUserCommand
{
    public function __construct(private UserService $userService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Change the password of a user.')
            ->setDefinition([
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('password', InputArgument::OPTIONAL, 'The password'),
            ])
            ->setHelp(
                <<<'EOT'
                    The <info>kimai:user:password</info> command changes the password of a user:

                      <info>php %command.full_name% matthieu</info>

                    This interactive shell will first ask you for a password.

                    You can alternatively specify the password as a second argument:

                      <info>php %command.full_name% susan_super newpassword</info>

                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        if (null !== $input->getArgument('password')) {
            $password = $input->getArgument('password');
        } else {
            $password = $this->askForPassword($input, $output);
        }

        $user = $this->userService->findUserByUsernameOrThrowException($username);

        $io = new SymfonyStyle($input, $output);

        try {
            $user->setPlainPassword($password);
            $this->userService->updateUser($user, ['PasswordUpdate']);
            $io->success(\sprintf('Changed password for user "%s".', $username));
        } catch (ValidationFailedException $ex) {
            $this->validationError($ex, $io);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
