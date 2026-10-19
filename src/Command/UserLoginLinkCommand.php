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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * @CloudRequired
 */
#[AsCommand(name: 'kimai:user:login-link', description: 'Create a URL that can be used to login as that user', hidden: true)]
final class UserLoginLinkCommand extends Command
{
    public function __construct(
        private readonly LoginLinkHandlerInterface $loginLink,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator
    )
    {
        parent::__construct();
        $this->addArgument('email', InputArgument::REQUIRED, 'The email of the user');
        $this->addOption('password-reset', null, InputOption::VALUE_NONE, 'Whether the user needs to reset the password afterwards');
        $this->addOption('all-auth', null, InputOption::VALUE_NONE, 'Ignore that the user is using an external authentication system');
        $this->addOption('absolute', null, InputOption::VALUE_NONE, 'Generate an absolute URL including scheme and host (configured via DEFAULT_URI)');
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

        if (!$user->isInternalUser() && !$input->getOption('all-auth')) {
            $io->error('User does not use internal login');

            return Command::FAILURE;
        }

        // the URL is generated from the router context (configured via DEFAULT_URI), passing a Request to
        // createLoginLink() would replace it and fail with "Untrusted Host" if TRUSTED_HOSTS is configured
        $context = $this->urlGenerator->getContext();
        $previousLocale = $context->getParameter('_locale');
        $context->setParameter('_locale', $user->getLanguage());

        // the firewall-aware login link handler needs an active request to determine the firewall
        $request = Request::create($context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/');
        $request->setLocale($user->getLanguage());
        $this->requestStack->push($request);

        try {
            $loginLink = $this->loginLink->createLoginLink($user)->getUrl();
        } finally {
            $this->requestStack->pop();
            $context->setParameter('_locale', $previousLocale);
        }

        if ($input->getOption('absolute') !== true) {
            $path = parse_url($loginLink, PHP_URL_PATH);
            $query = parse_url($loginLink, PHP_URL_QUERY);
            $loginLink = (\is_string($path) ? $path : '/') . (\is_string($query) ? '?' . $query : '');
        }

        if ($input->getOption('password-reset') === true) {
            $user->markPasswordRequested();
            $user->setRequiresPasswordReset(true);
            $this->userRepository->saveUser($user);
        }

        $output->writeln($loginLink);

        return Command::SUCCESS;
    }
}
