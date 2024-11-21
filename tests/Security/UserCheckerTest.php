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

/**
 * @covers \App\Security\UserChecker
 */
class UserCheckerTest extends TestCase
{
    public function testCheckPreAuthReturnsOnUnknownUserClass(): void
    {
        $sut = new UserChecker();

        try {
            $sut->checkPreAuth(new TestUserEntity());
        } catch (\Exception $ex) {
            $this->fail('UserChecker should not throw exception in checkPreAuth(), ' . $ex->getMessage());
        }
        $this->expectNotToPerformAssertions();
    }

    public function testCheckPostAuthReturnsOnUnknownUserClass(): void
    {
        $sut = new UserChecker();

        try {
            $sut->checkPostAuth(new TestUserEntity());
        } catch (\Exception $ex) {
            $this->fail('UserChecker should not throw exception in checkPostAuth(), ' . $ex->getMessage());
        }
        $this->expectNotToPerformAssertions();
    }

    public function testDisabledCannotLoginInCheckPreAuth(): void
    {
        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User account is disabled.');

        (new UserChecker())->checkPreAuth((new User())->setEnabled(false));
    }

    public function testDisabledCannotLoginInCheckPostAuth(): void
    {
        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User account is disabled.');

        (new UserChecker())->checkPostAuth((new User())->setEnabled(false));
    }
}
