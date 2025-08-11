<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Security\AclDecisionManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

#[CoversClass(AclDecisionManager::class)]
class AclDecisionManagerTest extends TestCase
{
    public function testFullyAuthenticated(): void
    {
        $manager = $this->createMock(AccessDecisionManagerInterface::class);
        $manager->expects($this->once())->method('decide')->willReturn(true);

        $token = $this->createMock(TokenInterface::class);

        $sut = new AclDecisionManager($manager);
        $result = $sut->isFullyAuthenticated($token);
        self::assertTrue($result);
    }

    public function testIsNotFullyAuthenticated(): void
    {
        $manager = $this->createMock(AccessDecisionManagerInterface::class);
        $manager->expects($this->once())->method('decide')->willReturn(false);

        $token = $this->createMock(TokenInterface::class);

        $sut = new AclDecisionManager($manager);
        $result = $sut->isFullyAuthenticated($token);
        self::assertFalse($result);
    }
}
