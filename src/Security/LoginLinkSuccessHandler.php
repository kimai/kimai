<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

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
 * A login link that was created as password reset (via email or the "kimai:user:login-link --password-reset" command)
 * requires the user to set a new password. The reset is bound to that one link (see User::markPasswordResetLink()):
 * any other login link is a normal login, even while a password reset request is pending. This is done here and not
 * when requesting the link, because requesting a password reset does not require authentication and must not have
 * any effect on the account.
 *
 * Requiring a password reset changes the security signature: all other sessions of the user end and the login link
 * becomes invalid. If 2FA is active, this is called before the TOTP code was entered.
 */
final class LoginLinkSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private readonly DefaultAuthenticationSuccessHandler $defaultHandler;

    public function __construct(
        HttpUtils $httpUtils,
        private readonly UserRepository $userRepository,
    )
    {
        $this->defaultHandler = new DefaultAuthenticationSuccessHandler($httpUtils);
    }

    /**
     * Called by Symfony (CustomAuthenticationSuccessHandler) with the firewall options, e.g. "default_target_path".
     *
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->defaultHandler->setOptions($options);
    }

    /**
     * Called by Symfony (CustomAuthenticationSuccessHandler), required to find the target path saved in the session.
     */
    public function setFirewallName(string $firewallName): void
    {
        $this->defaultHandler->setFirewallName($firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        // the hash identifies the link, it was validated by the LoginLinkHandler before
        $hash = $request->query->get('hash');

        if ($user instanceof User && \is_string($hash) && $user->isPasswordResetLink($hash)) {
            $user->setRequiresPasswordReset(true);
            $this->userRepository->saveUser($user);
        }

        return $this->defaultHandler->onAuthenticationSuccess($request, $token);
    }
}
