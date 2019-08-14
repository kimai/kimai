<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\User
 */
class UserTest extends TestCase
{
    public function testDefaultValues()
    {
        $user = new User();
        $this->assertInstanceOf(ArrayCollection::class, $user->getPreferences());
        $this->assertNull($user->getTitle());
        $this->assertNull($user->getAvatar());
        $this->assertNull($user->getAlias());
        $this->assertNull($user->getId());
        $this->assertNull($user->getApiToken());
        $this->assertNull($user->getPlainApiToken());
        $this->assertEquals(User::DEFAULT_LANGUAGE, $user->getLocale());

        $user->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y');
        $this->assertEquals('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y', $user->getAvatar());
        $user->setApiToken('nbvfdswe34567ujko098765rerfghbgvfcdsert');
        $this->assertEquals('nbvfdswe34567ujko098765rerfghbgvfcdsert', $user->getApiToken());
        $user->setPlainApiToken('https://www.gravatar.com/avatar/nbvfdswe34567ujko098765rerfghbgvfcdsert');
        $this->assertEquals('https://www.gravatar.com/avatar/nbvfdswe34567ujko098765rerfghbgvfcdsert', $user->getPlainApiToken());
        $user->setTitle('Mr. Code Blaster');
        $this->assertEquals('Mr. Code Blaster', $user->getTitle());
    }

    public function testDatetime()
    {
        $date = new \DateTime('+1 day');
        $user = new User();
        $user->setRegisteredAt($date);
        $this->assertEquals($date, $user->getRegisteredAt());
    }

    public function testPreferences()
    {
        $user = new User();
        $this->assertNull($user->getPreference('test'));
        $this->assertNull($user->getPreferenceValue('test'));
        $this->assertEquals('foo', $user->getPreferenceValue('test', 'foo'));

        $preference = new UserPreference();
        $preference
            ->setName('test')
            ->setValue('foobar');
        $user->addPreference($preference);
        $this->assertEquals('foobar', $user->getPreferenceValue('test', 'foo'));
        $this->assertEquals($preference, $user->getPreference('test'));

        $user->setPreferenceValue('test', 'Hello World');
        $this->assertEquals('Hello World', $user->getPreferenceValue('test', 'foo'));

        $this->assertNull($user->getPreferenceValue('test2'));
        $user->setPreferenceValue('test2', 'I like rain');
        $this->assertEquals('I like rain', $user->getPreferenceValue('test2'));
    }

    public function testToString()
    {
        $user = new User();

        $user->setUsername('bar');
        $this->assertEquals('bar', (string) $user);
        $this->assertEquals('bar', $user->getUsername());

        $user->setAlias('foo');
        $this->assertEquals('foo', (string) $user);
        $this->assertEquals('foo', $user->getAlias());
    }

    public function testGetLocale()
    {
        $sut = new User();
        $this->assertEquals(User::DEFAULT_LANGUAGE, $sut->getLocale());

        $language = new UserPreference();
        $language->setName(UserPreference::LOCALE);
        $language->setValue('fr');
        $sut->addPreference($language);

        $this->assertEquals('fr', $sut->getLocale());
    }

    public function testTeams()
    {
        $sut = new User();
        $team = new Team();
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getUsers());

        $sut->addTeam($team);
        self::assertEquals(1, $sut->getTeams()->count());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getUsers()[0]);
    }
}
