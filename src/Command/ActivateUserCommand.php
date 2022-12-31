<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\User\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'kimai:user:activate')]
final class ActivateUserCommand extends Command
{
    public function __construct(private UserService $userService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Activate a user')
            ->setDefinition([
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
            ])
            ->setHelp(
                <<<'EOT'
                    The <info>kimai:user:activate</info> command activates a user (so they will be able to log in):

                      <info>php %command.full_name% susan_super</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $user = $this->userService->findUserByUsernameOrThrowException($username);

        $io = new SymfonyStyle($input, $output);

        if (!$user->isEnabled()) {
            $user->setEnabled(true);
            $this->userService->updateUser($user);
            $io->success(sprintf('User "%s" has been activated.', $username));
        } else {
            $io->warning(sprintf('User "%s" is already active.', $username));
        }

        return Command::SUCCESS;
    }
}
