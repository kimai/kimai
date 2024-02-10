<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\MailConfiguration;
use App\Event\EmailEvent;
use App\EventSubscriber\EmailSubscriber;
use App\Mail\KimaiMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * @covers \App\EventSubscriber\EmailSubscriber
 */
class EmailSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = EmailSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(EmailEvent::class, $events);
        $methodName = $events[EmailEvent::class][0];
        $this->assertTrue(method_exists(EmailSubscriber::class, $methodName));
    }

    public function testSendIsTriggered(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $mailer = new KimaiMailer(
            new MailConfiguration('test@example.com'),
            $mailer
        );

        $sut = new EmailSubscriber($mailer);

        $event = new EmailEvent(new Email());

        $sut->onMailEvent($event);
    }
}
