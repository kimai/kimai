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
    public function getSut(): KimaiMailer
    {
        $config = $this->createMock(MailConfiguration::class);
        $config->expects($this->any())->method('getFromAddress')->willReturn('zippel@example.com');

        $mailer = $this->createMock(MailerInterface::class);

        return new KimaiMailer($config, $mailer);
    }

    public function testSendSetsFrom()
    {
        $user = new User();
        $user->setUsername('Testing');
        $user->setEmail('foo@example.com');
        $user->setAlias('Super User');

        $mailer = $this->getSut();
        $message = new Email();

        self::assertEquals([], $message->getFrom());

        $mailer->send($message);

        self::assertEquals([new Address('zippel@example.com')], $message->getFrom());
    }
}
