<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\EmailSelfRegistrationEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

/**
 * @covers \App\Event\EmailEvent
 * @covers \App\Event\UserEmailEvent
 * @covers \App\Event\EmailSelfRegistrationEvent
 */
class EmailSelfRegistrationEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $email = new Email();
        $email->text('sdfsdfsdfsdf');

        $sut = new EmailSelfRegistrationEvent($user, $email);

        self::assertSame($email, $sut->getEmail());
        self::assertSame($user, $sut->getUser());
    }
}
