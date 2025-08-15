<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\EmailEvent;
use App\Event\EmailPasswordResetEvent;
use App\Event\UserEmailEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

#[CoversClass(EmailEvent::class)]
#[CoversClass(UserEmailEvent::class)]
#[CoversClass(EmailPasswordResetEvent::class)]
class EmailPasswordResetEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $email = new Email();
        $email->text('sdfsdfsdfsdf');

        $sut = new EmailPasswordResetEvent($user, $email);

        self::assertSame($email, $sut->getEmail());
        self::assertSame($user, $sut->getUser());
    }
}
