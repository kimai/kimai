<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mail;

use App\Configuration\MailConfiguration;
use App\Entity\User;
use App\Mail\KimaiMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @covers \App\Mail\KimaiMailer
 */
class KimaiMailerTest extends TestCase
{
    public function getSut(?MailerInterface $mailer = null): KimaiMailer
    {
        $config = new MailConfiguration('zippel@example.com');

        if ($mailer === null) {
            $mailer = $this->createMock(MailerInterface::class);
            $mailer->expects(self::once())->method('send');
        }

        return new KimaiMailer($config, $mailer);
    }

    public function testSendSetsFromHeaderFromFallback(): void
    {
        $user = new User();
        $user->setUserIdentifier('Testing');
        $user->setEmail('foo@example.com');
        $user->setAlias('Super User');

        $mailer = $this->getSut();
        $message = new Email();

        self::assertEquals([], $message->getFrom());

        $mailer->send($message);

        self::assertEquals([new Address('zippel@example.com', 'Kimai')], $message->getFrom());
    }

    public function testSendToUserSetsFromHeaderFromFallback(): void
    {
        $user = new User();
        $user->setUserIdentifier('Testing');
        $user->setEmail('foo@example.com');
        $user->setAlias('Super User');
        $user->setEnabled(true);

        $mailer = $this->getSut();
        $message = new Email();

        $mailer->sendToUser($user, $message);

        self::assertEquals([new Address('zippel@example.com', 'Kimai')], $message->getFrom());
    }

    public function testSendToUserSendsEmailWhenUserIsEnabledAndHasEmail(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isEnabled')->willReturn(true);
        $user->method('getEmail')->willReturn('foo-bar@example.com');

        $email = new Email();
        self::assertEquals([], $email->getTo());
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with($email);

        $sut = $this->getSut($mailer);

        $sut->sendToUser($user, $email);
        self::assertEquals([new Address('zippel@example.com', 'Kimai')], $email->getFrom());
        self::assertEquals([new Address('foo-bar@example.com')], $email->getTo());
    }

    public function testSendToUserDoesNotSendEmailWhenUserIsDisabled(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isEnabled')->willReturn(false);
        $user->method('getEmail')->willReturn('user@example.com');

        $email = new Email();
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        $sut = $this->getSut($mailer);

        $sut->sendToUser($user, $email);
    }

    public function testSendToUserDoesNotSendEmailWhenUserHasNoEmail(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isEnabled')->willReturn(true);
        $user->method('getEmail')->willReturn(null);

        $email = new Email();
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        $sut = $this->getSut($mailer);

        $sut->sendToUser($user, $email);
    }

    public function testSThrowsOnEmptyFromAddress(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing email "from" address');

        $user = $this->createMock(User::class);
        $user->method('isEnabled')->willReturn(true);
        $user->method('getEmail')->willReturn('test@example.com');

        $email = new Email();
        $config = new MailConfiguration('');
        $mailer = $this->createMock(MailerInterface::class);
        $sut = new KimaiMailer($config, $mailer);

        $sut->sendToUser($user, $email);
    }
}
