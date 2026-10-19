<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Model;

use App\Entity\User;
use App\Form\Model\TotpActivation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TotpActivation::class)]
class TotpActivationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $user = new User();
        $user->setUserIdentifier('foo-bar');
        $sut = new TotpActivation($user, 'JBSWY3DPEHPK3PXP');

        self::assertSame($user, $sut->getUser());
        self::assertSame('JBSWY3DPEHPK3PXP', $sut->getSecret());
        self::assertTrue($sut->isTotpAuthenticationEnabled());
        self::assertSame('foo-bar', $sut->getTotpAuthenticationUsername());
        self::assertSame('JBSWY3DPEHPK3PXP', $sut->getTotpAuthenticationConfiguration()->getSecret());
        // the pending secret must use the same TOTP settings as the user
        self::assertEquals($user->createTotpAuthenticationConfiguration('JBSWY3DPEHPK3PXP'), $sut->getTotpAuthenticationConfiguration());
        // the pending secret must not be stored in the user
        self::assertFalse($user->hasTotpSecret());
        self::assertNull($sut->getCode());
        $sut->setCode('');
        self::assertEquals('', $sut->getCode());
        $sut->setCode('jztfztfjzfjhgfjhgfjhgfjtzfiuzgbljv');
        self::assertEquals('jztfztfjzfjhgfjhgfjhgfjtzfiuzgbljv', $sut->getCode());
        $sut->setCode(null);
        self::assertNull($sut->getCode());
    }
}
