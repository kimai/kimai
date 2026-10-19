<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\User;

use App\Entity\User;
use App\User\ExternalUserSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validation;

#[CoversClass(ExternalUserSanitizer::class)]
class ExternalUserSanitizerTest extends TestCase
{
    private function createSut(LoggerInterface $logger): ExternalUserSanitizer
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        return new ExternalUserSanitizer($validator, $logger);
    }

    public function testEmptyAvatarIsNotTouched(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $user = new User();
        $user->setUserIdentifier('foobar');

        $this->createSut($logger)->sanitize($user);

        self::assertNull($user->getAvatar());
    }

    public function testAbsoluteUrlIsNotTouched(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAvatar('https://www.example.com/avatar.png');

        $this->createSut($logger)->sanitize($user);

        self::assertEquals('https://www.example.com/avatar.png', $user->getAvatar());
    }

    public function testRelativeUrlIsRemovedAndLogged(): void
    {
        $message = '';
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->willReturnCallback(function (string $logMessage) use (&$message) {
            $message = $logMessage;
        });

        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAvatar('/images/avatar.png');

        $this->createSut($logger)->sanitize($user);

        self::assertNull($user->getAvatar());
        self::assertStringContainsString('/images/avatar.png', $message);
        self::assertStringContainsString('"avatar"', $message);
        self::assertStringContainsString('"foobar"', $message);
    }

    public function testTooLongUrlIsRemovedAndShortenedInLog(): void
    {
        $message = '';
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->willReturnCallback(function (string $logMessage) use (&$message) {
            $message = $logMessage;
        });

        $avatar = 'https://www.example.com/' . str_repeat('a', 250) . '.png';
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAvatar($avatar);

        $this->createSut($logger)->sanitize($user);

        self::assertNull($user->getAvatar());
        self::assertStringContainsString(substr($avatar, 0, 100) . '...', $message);
        self::assertStringNotContainsString($avatar, $message);
    }
}
