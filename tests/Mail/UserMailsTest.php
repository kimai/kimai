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
use App\Mail\UserMails;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Mail\UserMails
 */
class UserMailsTest extends TestCase
{
    public function getSut(): UserMails
    {
        $config = $this->createMock(MailConfiguration::class);
        $config->expects($this->any())->method('getFromAddress')->willReturn('zippel@example.com');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->willReturnCallback(function (Email $message) {
            self::assertEquals([new Address('zippel@example.com')], $message->getFrom());
            self::assertEquals([new Address('foo@example.com')], $message->getTo());
        });
        $kimaiMailer = new KimaiMailer($config, $mailer);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())->method('trans')->willReturn('foo');

        return new UserMails($kimaiMailer, $router, $translator);
    }

    public function testSendConfirmationEmailMessage()
    {
        $user = new User();
        $user->setUsername('Testing');
        $user->setEmail('foo@example.com');
        $user->setAlias('Super User');

        $mailer = $this->getSut();
        $mailer->sendConfirmationEmailMessage($user);
    }

    public function testSendResettingEmailMessage()
    {
        $user = new User();
        $user->setUsername('Testing');
        $user->setEmail('foo@example.com');
        $user->setAlias('Super User');

        $mailer = $this->getSut();
        $mailer->sendResettingEmailMessage($user);
    }
}
