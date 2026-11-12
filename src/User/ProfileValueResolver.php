<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * Loads the user for the profile routes by the "username" route attribute.
 *
 * An unknown user is answered with the same "access denied" as an existing user who cannot be accessed,
 * so the profile routes cannot be used to find out which usernames or email addresses exist.
 */
#[AsTargetedValueResolver]
final class ProfileValueResolver implements ValueResolverInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @return iterable<User>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== User::class) {
            return [];
        }

        $username = $request->attributes->get('username');

        if (!\is_string($username) || $username === '') {
            throw new AccessDeniedException('Access denied.');
        }

        try {
            return [$this->userRepository->loadUserByIdentifier($username)];
        } catch (UserNotFoundException) {
            throw new AccessDeniedException('Access denied.');
        }
    }
}
