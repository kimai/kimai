<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\User\UserService;
use App\Utils\CommandStyle;
use App\Validator\ValidationFailedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangePasswordCommand extends AbstractUserCommand
{
    private $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    protected function configure()
    {
        $this
            ->setName('kimai:user:password')
            ->setAliases(['fos:user:change-password'])
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');

        if (null !== $input->getArgument('password')) {
            $password = $input->getArgument('password');
        } else {
            $password = $this->askForPassword($input, $output);
        }

        $user = $this->userService->findUserByUsernameOrThrowException($username);

        $io = new CommandStyle($input, $output);

        try {
            $user->setPlainPassword($password);
            $this->userService->updateUser($user, ['PasswordUpdate']);
            $io->success(sprintf('Changed password for user "%s".', $username));
        } catch (ValidationFailedException $ex) {
            $io->validationError($ex);

            return 2;
        }

        return 0;
    }
}
