<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @covers \App\Security\UserChecker
 */
class UserCheckerTest extends TestCase
{
    public function testCheckPreAuth()
    {
        $sut = new UserChecker();
        $user = new User();

        try {
            $sut->checkPreAuth($user);
        } catch (\Exception $ex) {
            $this->fail('UserChecker should not throw exception in checkPreAuth()');
        }
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\LockedException
     */
    public function testDisabledCannotLogin()
    {
        $sut = new UserChecker();
        $user = new User();
        $user->setEnabled(false);

        $sut->checkPostAuth($user);
    }

    public function testCheckPostAuth()
    {
        $sut = new UserChecker();

        $mock = $this->getMockBuilder(UserInterface::class)->setMethods(['isEnabled'])->getMockForAbstractClass();
        $mock->expects($this->never())->method('isEnabled')->willReturn(false);

        $sut->checkPostAuth($mock);
        $this->assertTrue(true);
    }
}
