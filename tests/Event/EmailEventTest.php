<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\EmailEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

/**
 * @covers \App\Event\EmailEvent
 */
class EmailEventTest extends TestCase
{
    public function testGetter(): void
    {
        $email = new Email();
        $email->text('sdfsdfsdfsdf');

        $sut = new EmailEvent($email);

        $this->assertEquals($email, $sut->getEmail());
    }
}
