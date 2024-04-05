<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[AsCommand(name: 'kimai:user:login-link', description: 'Create a URL that can be used to login as that user', hidden: true)]
/**
 * @CloudRequired
 */
final class UserLoginLinkCommand extends Command
{
    public function __construct(
        private readonly LoginLinkHandlerInterface $loginLink,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack
    )
    {
        parent::__construct();
        $this->addArgument('email', InputArgument::REQUIRED, 'The email of the user');
        $this->addOption('password-reset', null, InputOption::VALUE_NONE, 'Whether the user needs to reset the password afterwards');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if ($email === null || $email === '') {
            $io->error('Need email to create login URL');

            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            $io->error('Need username to create login URL');

            return Command::FAILURE;
        }

        if (!$user->isEnabled()) {
            $io->error('User is not enabled');

            return Command::FAILURE;
        }

        if (!$user->isInternalUser()) {
            $io->error('User does not use internal login');

            return Command::FAILURE;
        }

        $request = new Request();
        $request->setLocale($user->getLanguage());
        $this->requestStack->push($request);

        $loginLinkDetails = $this->loginLink->createLoginLink($user, $request);
        $loginLink = $loginLinkDetails->getUrl();

        if ($input->getOption('password-reset') === true) {
            $user->markPasswordRequested();
            $user->setRequiresPasswordReset(true);
            $this->userRepository->saveUser($user);
        }

        $output->writeln($loginLink);

        return Command::SUCCESS;
    }
}
