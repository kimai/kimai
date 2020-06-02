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
        self::assertNull($user->getTitle());
        self::assertNull($user->getDisplayName());
        self::assertNull($user->getAvatar());
        self::assertNull($user->getAlias());
        self::assertNull($user->getId());
        self::assertNull($user->getApiToken());
        self::assertNull($user->getPlainApiToken());
        self::assertEquals(User::DEFAULT_LANGUAGE, $user->getLocale());

        $user->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y');
        self::assertEquals('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y', $user->getAvatar());

        $user->setApiToken('nbvfdswe34567ujko098765rerfghbgvfcdsert');
        self::assertEquals('nbvfdswe34567ujko098765rerfghbgvfcdsert', $user->getApiToken());

        $user->setPlainApiToken('https://www.gravatar.com/avatar/nbvfdswe34567ujko098765rerfghbgvfcdsert');
        self::assertEquals('https://www.gravatar.com/avatar/nbvfdswe34567ujko098765rerfghbgvfcdsert', $user->getPlainApiToken());

        $user->setTitle('Mr. Code Blaster');
        self::assertEquals('Mr. Code Blaster', $user->getTitle());
    }

    public function testAuth()
    {
        $user = new User();

        self::assertEquals(User::AUTH_INTERNAL, $user->getAuth());
        self::assertFalse($user->isLdapUser());
        self::assertFalse($user->isSamlUser());
        self::assertTrue($user->isInternalUser());

        $user->setAuth(User::AUTH_LDAP);
        self::assertEquals(User::AUTH_LDAP, $user->getAuth());
        self::assertTrue($user->isLdapUser());
        self::assertFalse($user->isSamlUser());
        self::assertFalse($user->isInternalUser());

        $user->setAuth(User::AUTH_SAML);
        self::assertEquals(User::AUTH_SAML, $user->getAuth());
        self::assertFalse($user->isLdapUser());
        self::assertTrue($user->isSamlUser());
        self::assertFalse($user->isInternalUser());

        $user->setAuth(User::AUTH_INTERNAL);
        self::assertEquals(User::AUTH_INTERNAL, $user->getAuth());
        self::assertFalse($user->isLdapUser());
        self::assertFalse($user->isSamlUser());
        self::assertTrue($user->isInternalUser());
    }

    public function testDatetime()
    {
        $date = new \DateTime('+1 day');
        $user = new User();
        $user->setRegisteredAt($date);
        self::assertEquals($date, $user->getRegisteredAt());
    }

    public function testPreferences()
    {
        $user = new User();
        self::assertNull($user->getPreference('test'));
        self::assertNull($user->getPreferenceValue('test'));
        self::assertEquals('foo', $user->getPreferenceValue('test', 'foo'));

        $preference = new UserPreference();
        $preference
            ->setName('test')
            ->setValue('foobar');
        $user->addPreference($preference);
        self::assertEquals('foobar', $user->getPreferenceValue('test', 'foo'));
        self::assertEquals($preference, $user->getPreference('test'));

        $user->setPreferenceValue('test', 'Hello World');
        self::assertEquals('Hello World', $user->getPreferenceValue('test', 'foo'));

        self::assertNull($user->getPreferenceValue('test2'));
        $user->setPreferenceValue('test2', 'I like rain');
        self::assertEquals('I like rain', $user->getPreferenceValue('test2'));
    }

    public function testDisplayName()
    {
        $user = new User();

        $user->setUsername('bar');
        self::assertEquals('bar', $user->getDisplayName());
        self::assertEquals('bar', $user->getUsername());
        self::assertEquals('bar', (string) $user);

        $user->setAlias('foo');
        self::assertEquals('foo', $user->getAlias());
        self::assertEquals('bar', $user->getUsername());
        self::assertEquals('foo', $user->getDisplayName());
        self::assertEquals('foo', (string) $user);
    }

    public function testGetLocale()
    {
        $sut = new User();
        self::assertEquals(User::DEFAULT_LANGUAGE, $sut->getLocale());

        $language = new UserPreference();
        $language->setName(UserPreference::LOCALE);
        $language->setValue('fr');
        $sut->addPreference($language);

        self::assertEquals('fr', $sut->getLocale());
    }

    public function testTeams()
    {
        $sut = new User();
        $team = new Team();
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getUsers());

        $sut->addTeam($team);
        self::assertCount(1, $sut->getTeams());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getUsers()[0]);

        self::assertFalse($sut->isTeamleadOf($team));
        self::assertTrue($sut->isInTeam($team));

        $team2 = new Team();
        self::assertFalse($sut->isInTeam($team2));
        self::assertFalse($sut->isTeamleadOf($team2));
        $team2->setTeamLead($sut);
        self::assertTrue($sut->isTeamleadOf($team2));
        self::assertTrue($sut->isInTeam($team2));

        $sut->removeTeam(new Team());
        self::assertCount(2, $sut->getTeams());
        $sut->removeTeam($team);
        self::assertCount(1, $sut->getTeams());
        $sut->removeTeam($team2);
        self::assertCount(0, $sut->getTeams());
    }

    public function testRoles()
    {
        $sut = new User();
        self::assertFalse($sut->isTeamlead());
        $sut->addRole(User::ROLE_ADMIN);
        self::assertFalse($sut->isTeamlead());
        $sut->addRole(User::ROLE_TEAMLEAD);
        self::assertTrue($sut->isTeamlead());
    }

    /**
     * This functionality was added, because these fields can be set via external providers (LDAP, SAML) and
     * an invalid length should not result in errors.
     *
     * @see #1562
     */
    public function testMaxLength()
    {
        $sut = new User();
        $sut->setAlias('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        self::assertEquals(60, mb_strlen($sut->getAlias()));
        $sut->setAlias('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxAAAAA');
        self::assertEquals(60, mb_strlen($sut->getAlias()));
        $sut->setAlias('万政提質打録施熟活者韓症写気当。規談表有部確暑将回優隊見竜能南事。竹阪板府入違護究兵厚能提。済伸知題熱正写場京誉事週在複今徳際供。審利世連手阿量携泉指済像更映刊政病世。熱楽時予資方賀月改洋者職原桜提増脚職。気公誌荒原輝文治察専及唱戦白廃模書。着授健出山力集出止員捉害実載措明国無今。棋出陶供供知機使協物確講最新両。');
        self::assertEquals(60, mb_strlen($sut->getAlias()));
        $sut->setTitle('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        self::assertEquals(50, mb_strlen($sut->getTitle()));
        $sut->setTitle('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxAAAAAA');
        self::assertEquals(50, mb_strlen($sut->getTitle()));
    }

    public function testPreferencesCollectionIsCreatedOnBrokenUser()
    {
        // this code is only used in some rare edge cases, maybe even only in development ...
        // lets keep it, as it occured during the work on SAML authentication
        $sut = new User();

        $preference = new UserPreference();
        $preference
            ->setName('test')
            ->setValue('foobar');

        $property = new \ReflectionProperty(User::class, 'preferences');
        $property->setAccessible(true);
        $property->setValue($sut, null);

        // make sure that addPreference will work, even if the internal collection was set to null
        $sut->addPreference($preference);

        self::assertEquals('foobar', $sut->getPreferenceValue('test'));
    }
}
