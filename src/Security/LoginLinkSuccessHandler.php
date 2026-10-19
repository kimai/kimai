<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Called after a login link was validated, see "login_link.success_handler" in security.yaml.
 *
 * A login link that was requested as password reset (via email or the "kimai:user:login-link --password-reset" command)
 * requires the user to set a new password. This is done here and not when requesting the link, because requesting a
 * password reset does not require authentication and must not have any effect on the account.
 *
 * Requiring a password reset changes the security signature: all other sessions of the user end and the login link
 * becomes invalid. If 2FA is active, this is called before the TOTP code was entered.
 */
final class LoginLinkSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private readonly DefaultAuthenticationSuccessHandler $defaultHandler;

    public function __construct(
        HttpUtils $httpUtils,
        private readonly SystemConfiguration $configuration,
        private readonly UserRepository $userRepository,
    )
    {
        $this->defaultHandler = new DefaultAuthenticationSuccessHandler($httpUtils);
    }

    /**
     * Called by Symfony with the firewall options (e.g. "default_target_path").
     *
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->defaultHandler->setOptions($options);
    }

    public function setFirewallName(string $firewallName): void
    {
        $this->defaultHandler->setFirewallName($firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof User && !$user->requiresPasswordReset() && $user->isPasswordRequestNonExpired($this->configuration->getPasswordResetRetryLifetime())) {
            $user->setRequiresPasswordReset(true);
            $this->userRepository->saveUser($user);
        }

        return $this->defaultHandler->onAuthenticationSuccess($request, $token);
    }
}
