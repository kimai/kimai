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
        $sut = new TotpActivation($user);

        self::assertSame($user, $sut->getUser());
        self::assertNull($sut->getCode());
        $sut->setCode('');
        self::assertEquals('', $sut->getCode());
        $sut->setCode('jztfztfjzfjhgfjhgfjhgfjtzfiuzgbljv');
        self::assertEquals('jztfztfjzfjhgfjhgfjhgfjtzfiuzgbljv', $sut->getCode());
        $sut->setCode(null);
        self::assertNull($sut->getCode());
    }
}
