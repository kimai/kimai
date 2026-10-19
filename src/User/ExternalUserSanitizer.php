<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Removes invalid values, which were mapped from an external user directory (e.g. LDAP or SAML).
 *
 * A broken attribute mapping must not prevent the login: the user is saved without the invalid
 * value and administrators find the reason in the log.
 */
final class ExternalUserSanitizer
{
    /**
     * Maximum length of an invalid value in the log message.
     */
    private const MAX_VALUE_LENGTH = 100;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sanitize(User $user): void
    {
        // the avatar is a URL, which cannot be verified by the external system
        if ($user->getAvatar() !== null && !$this->isValidField($user, 'avatar', $user->getAvatar())) {
            $user->setAvatar(null);
        }
    }

    private function isValidField(User $user, string $field, string $value): bool
    {
        $violations = $this->validator->validateProperty($user, $field);

        if ($violations->count() === 0) {
            return true;
        }

        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            $value = mb_substr($value, 0, self::MAX_VALUE_LENGTH) . '...';
        }

        $this->logger->warning(
            \sprintf(
                'Ignoring invalid value "%s" for the field "%s" of user "%s", please check your attribute mapping: %s',
                $value,
                $field,
                $user->getUserIdentifier(),
                implode(', ', $messages)
            )
        );

        return false;
    }
}
