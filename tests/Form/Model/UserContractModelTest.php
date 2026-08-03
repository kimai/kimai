<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Model;

use App\Entity\User;
use App\Form\Model\UserContractModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserContractModel::class)]
class UserContractModelTest extends TestCase
{
    public function testIssetAlwaysReturnsTrue(): void
    {
        $user = new User();
        $sut = new UserContractModel($user);

        self::assertTrue($sut->__isset('alias'));
        self::assertTrue($sut->__isset('unknownPreference'));
    }

    public function testSetAndGetExistingUserPropertyUsesUserMethods(): void
    {
        $user = new User();
        $sut = new UserContractModel($user);

        $sut->__set('alias', 'contract-user');

        self::assertSame('contract-user', $sut->__get('alias'));
        self::assertSame('contract-user', $user->getAlias());
    }

    public function testSetAndGetExistingPreferenceBackedMethodUsesUserMethod(): void
    {
        $user = new User();
        $sut = new UserContractModel($user);

        $sut->__set('workContractMode', 'default');

        self::assertSame('default', $sut->__get('workContractMode'));
        self::assertSame('default', $user->getWorkContractMode());
    }

    public function testSetAndGetUnknownPropertyUsesPreferenceFallback(): void
    {
        $user = new User();
        $sut = new UserContractModel($user);

        $sut->__set('customContractField', 'weekly');

        self::assertSame('weekly', $sut->__get('customContractField'));
        self::assertSame('weekly', $user->getPreferenceValue('customContractField'));
    }

    public function testSetUnknownPropertyAllowsNullPreferenceValue(): void
    {
        $user = new User();
        $sut = new UserContractModel($user);

        $sut->__set('customContractField', null);

        self::assertNull($sut->__get('customContractField'));
        self::assertNull($user->getPreferenceValue('customContractField'));
    }

    public function testGetUnknownPropertyWithoutPreferenceReturnsNull(): void
    {
        $sut = new UserContractModel(new User());

        self::assertNull($sut->__get('missingPreference'));
    }

    public function testSetUnknownPropertyRejectsNonScalarValues(): void
    {
        $sut = new UserContractModel(new User());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value passed');

        $sut->__set('customContractField', ['invalid']);
    }
}
