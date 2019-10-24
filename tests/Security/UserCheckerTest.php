<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\User as SymfonyUser;

/**
 * @covers \App\Security\UserChecker
 */
class UserCheckerTest extends TestCase
{
    public function testCheckPreAuthReturnsOnUnknownUserClass()
    {
        $sut = new UserChecker();

        try {
            $sut->checkPreAuth(new SymfonyUser('sdf', null));
        } catch (\Exception $ex) {
            $this->fail('UserChecker should not throw exception in checkPreAuth(), ' . $ex->getMessage());
        }
        $this->assertTrue(true);
    }

    public function testCheckPostAuthReturnsOnUnknownUserClass()
    {
        $sut = new UserChecker();

        try {
            $sut->checkPostAuth(new SymfonyUser('sdf', null));
        } catch (\Exception $ex) {
            $this->fail('UserChecker should not throw exception in checkPostAuth(), ' . $ex->getMessage());
        }
        $this->assertTrue(true);
    }

    public function testDisabledCannotLoginInCheckPreAuth()
    {
        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User account is disabled.');

        (new UserChecker())->checkPreAuth((new User())->setEnabled(false));
    }

    public function testDisabledCannotLoginInCheckPostAuth()
    {
        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User account is disabled.');

        (new UserChecker())->checkPostAuth((new User())->setEnabled(false));
    }
}
