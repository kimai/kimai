<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\User;
use App\Security\RolePermissionManager;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A voter to check permissions on API.
 *
 * @extends Voter<string, null>
 */
final class ApiVoter extends Voter
{
    public function __construct(
        private readonly RolePermissionManager $permissionManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    )
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === 'API';
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === 'null';
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject === null && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // this check does not work, because remember_me sessions would not pass this check
        // as the frontend uses the API, the user need to be able to use the API via session, even if not "fully authenticated"
        // !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY', $user)

        // the instanceof check is not mentioned in the official docs https://symfony.com/bundles/SchebTwoFactorBundle/8.x/index.html
        // but it should be a tiny bit faster than asking the TwoFactorInProgressVoter, which does the same ...
        // as we rely on internal bundle knowledge, we keep the defense-in-depth branch here as well

        if (
            $token instanceof TwoFactorTokenInterface ||
            $this->authorizationChecker->isGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS', $user)
        ) {
            return false;
        }

        // derived from AccessTokenSuccessHandler
        if ($token->hasAttribute('api-token')) {
            return $this->permissionManager->hasRolePermission($user, 'api_access');
        }

        return true;
    }
}
