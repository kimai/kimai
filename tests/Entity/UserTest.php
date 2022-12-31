<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Constants;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Tests\Security\TestUserEntity;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @covers \App\Entity\User
 */
class UserTest extends TestCase
{
    public function testDefaultValues()
    {
        $user = new User();
        self::assertInstanceOf(EquatableInterface::class, $user);
        self::assertInstanceOf(UserInterface::class, $user);
        $this->assertInstanceOf(ArrayCollection::class, $user->getPreferences());
        self::assertNull($user->getTitle());
        self::assertNull($user->getAvatar());
        self::assertNull($user->getAlias());
        self::assertNull($user->getId());
        self::assertNull($user->getAccountNumber());
        self::assertNull($user->getApiToken());
        self::assertNull($user->getPlainApiToken());
        self::assertNull($user->getPasswordRequestedAt());
        self::assertEquals(User::DEFAULT_LANGUAGE, $user->getLocale());
        self::assertFalse($user->hasTeamAssignment());
        self::assertFalse($user->canSeeAllData());
        self::assertFalse($user->isExportDecimal());
        self::assertFalse($user->isSystemAccount());

        $user->setUserIdentifier('foo');
        self::assertEquals('foo', $user->getUserIdentifier());
        self::assertEquals('foo', $user->getDisplayName());
        $user->setAlias('BAR');
        self::assertEquals('BAR', $user->getDisplayName());

        $user->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y');
        self::assertEquals('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y', $user->getAvatar());

        $user->setApiToken('nbvfdswe34567ujko098765rerfghbgvfcdsert');
        self::assertEquals('nbvfdswe34567ujko098765rerfghbgvfcdsert', $user->getApiToken());

        $user->setPlainApiToken('https://www.gravatar.com/avatar/nbvfdswe34567ujko098765rerfghbgvfcdsert');
        self::assertEquals('https://www.gravatar.com/avatar/nbvfdswe34567ujko098765rerfghbgvfcdsert', $user->getPlainApiToken());

        $user->setTitle('Mr. Code Blaster');
        self::assertEquals('Mr. Code Blaster', $user->getTitle());

        $user->setAccountNumber('A-058375');
        self::assertEquals('A-058375', $user->getAccountNumber());
    }

    public function testColor()
    {
        $sut = new User();
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setColor(Constants::DEFAULT_COLOR);
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setColor('#000000');
        self::assertEquals('#000000', $sut->getColor());
        self::assertTrue($sut->hasColor());
    }

    public function testWizards()
    {
        $sut = new User();
        // internal name may not be changed
        self::assertNull($sut->getPreferenceValue('__wizards__'));
        self::assertFalse($sut->hasSeenWizard('intro'));
        $sut->setWizardAsSeen('intro');
        self::assertTrue($sut->hasSeenWizard('intro'));
        self::assertNotNull($sut->getPreferenceValue('__wizards__'));
        $sut->setWizardAsSeen('intro');
        self::assertTrue($sut->hasSeenWizard('intro'));
        self::assertFalse($sut->hasSeenWizard('profile'));
        $sut->setWizardAsSeen('profile');
        self::assertTrue($sut->hasSeenWizard('profile'));
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

    public function testPasswordRequestedAt()
    {
        $date = new \DateTime('-60 minutes');
        $sut = new User();
        self::assertFalse($sut->isPasswordRequestNonExpired(3599));

        self::assertNull($sut->getPasswordRequestedAt());
        $sut->setPasswordRequestedAt($date);
        self::assertEquals($date, $sut->getPasswordRequestedAt());
        self::assertFalse($sut->isPasswordRequestNonExpired(3599));
        // 10 seconds just to make sure it doesn't expire by accident
        self::assertTrue($sut->isPasswordRequestNonExpired(3610));
    }

    public function testPreferences()
    {
        $user = new User();
        self::assertNull($user->getPreference('test'));
        self::assertNull($user->getPreferenceValue('test'));
        self::assertEquals('foo', $user->getPreferenceValue('test', 'foo'));

        $preference = new UserPreference('test', 'foobar');
        $user->addPreference($preference);
        self::assertEquals('foobar', $user->getPreferenceValue('test', 'foo'));
        self::assertEquals($preference, $user->getPreference('test'));

        $user->setPreferenceValue('test', 'Hello World');
        self::assertEquals('Hello World', $user->getPreferenceValue('test', 'foo'));

        self::assertNull($user->getPreferenceValue('test2'));
        $user->setPreferenceValue('test2', 'I like rain');
        self::assertEquals('I like rain', $user->getPreferenceValue('test2'));

        $user->setPreferenceValue('export_decimal', true);
        self::assertTrue($user->isExportDecimal());
    }

    public function testDisplayName()
    {
        $user = new User();

        $user->setUserIdentifier('bar');
        self::assertEquals('bar', $user->getDisplayName());
        self::assertEquals('bar', $user->getUserIdentifier());
        self::assertEquals('bar', (string) $user);

        $user->setAlias('foo');
        self::assertEquals('foo', $user->getAlias());
        self::assertEquals('bar', $user->getUserIdentifier());
        self::assertEquals('foo', $user->getDisplayName());
        self::assertEquals('foo', (string) $user);
    }

    public function testGetUsername()
    {
        $user = new User();

        $user->setUserIdentifier('bar');
        self::assertEquals('bar', $user->getDisplayName());
        self::assertEquals('bar', $user->getUserIdentifier());
        self::assertEquals('bar', (string) $user);

        $user->setAlias('foo');
        self::assertEquals('foo', $user->getAlias());
        self::assertEquals('bar', $user->getUserIdentifier());
        self::assertEquals('foo', $user->getDisplayName());
        self::assertEquals('foo', (string) $user);
    }

    public function testGetLocale()
    {
        $sut = new User();
        self::assertEquals(User::DEFAULT_LANGUAGE, $sut->getLocale());

        $language = new UserPreference(UserPreference::LOCALE, 'fr');
        $sut->addPreference($language);

        self::assertEquals('fr', $sut->getLocale());
    }

    public function testTeams()
    {
        $sut = new User();
        $user = new User();
        $team = new Team('foo');
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getUsers());

        $member1 = new TeamMember();
        $member1->setUser($sut);
        $member1->setTeam($team);

        $sut->addMembership($member1);
        self::assertCount(1, $sut->getTeams());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getUsers()[0]);
        self::assertTrue($sut->hasTeamAssignment());
        self::assertFalse($sut->hasTeamMember($user));

        $team->addUser($user);
        self::assertTrue($sut->hasTeamMember($user));

        self::assertFalse($sut->isTeamleadOf($team));
        self::assertTrue($sut->isInTeam($team));

        $team2 = new Team('foo');
        self::assertFalse($sut->isInTeam($team2));
        self::assertFalse($sut->isTeamleadOf($team2));
        $team2->addTeamLead($sut);
        self::assertTrue($sut->isTeamleadOf($team2));
        self::assertTrue($sut->isInTeam($team2));

        self::assertCount(2, $sut->getTeams());
        $sut->removeMembership(new TeamMember());
        self::assertCount(2, $sut->getTeams());
        $sut->removeMembership($member1);
        self::assertCount(1, $sut->getTeams());
        self::assertTrue($sut->hasTeamAssignment());
        $team2->removeUser($sut);
        self::assertCount(0, $sut->getTeams());
        self::assertFalse($sut->hasTeamAssignment());
    }

    public function testRoles()
    {
        $sut = new User();
        self::assertFalse($sut->canSeeAllData());
        self::assertFalse($sut->isAdmin());
        self::assertFalse($sut->isTeamlead());

        $sut->addRole(User::ROLE_ADMIN);
        self::assertFalse($sut->canSeeAllData());
        self::assertTrue($sut->isAdmin());
        self::assertFalse($sut->isTeamlead());

        $sut->addRole(User::ROLE_TEAMLEAD);
        self::assertTrue($sut->hasTeamleadRole());
        self::assertFalse($sut->canSeeAllData());

        $sut->removeRole(User::ROLE_ADMIN);
        self::assertFalse($sut->canSeeAllData());
        self::assertFalse($sut->isAdmin());

        $sut->addRole(User::ROLE_SUPER_ADMIN);
        self::assertTrue($sut->canSeeAllData());
        self::assertFalse($sut->isAdmin());
        self::assertTrue($sut->isSuperAdmin());

        $sut->removeRole(User::ROLE_SUPER_ADMIN);
        self::assertFalse($sut->canSeeAllData());
        self::assertFalse($sut->isSuperAdmin());
        self::assertTrue($sut->hasTeamleadRole());

        $sut->setSuperAdmin(true);
        self::assertTrue($sut->isSuperAdmin());

        $sut->setSuperAdmin(false);
        self::assertFalse($sut->isSuperAdmin());
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

        $preference = new UserPreference('test', 'foobar');

        $property = new \ReflectionProperty(User::class, 'preferences');
        $property->setAccessible(true);
        $property->setValue($sut, null);

        // make sure that addPreference will work, even if the internal collection was set to null
        $sut->addPreference($preference);

        self::assertEquals('foobar', $sut->getPreferenceValue('test'));
    }

    public function testCanSeeAllData()
    {
        $sut = new User();
        $sut->addRole(User::ROLE_USER);
        self::assertFalse($sut->canSeeAllData());
        self::assertTrue($sut->initCanSeeAllData(true));
        self::assertTrue($sut->canSeeAllData());
        self::assertFalse($sut->initCanSeeAllData(true));
    }

    public function testSystemAccount()
    {
        $sut = new User();
        self::assertFalse($sut->isSystemAccount());
        $sut->setSystemAccount(true);
        self::assertTrue($sut->isSystemAccount());
        $sut->setSystemAccount(false);
        self::assertFalse($sut->isSystemAccount());
    }

    public function testExportAnnotations()
    {
        $sut = new AnnotationExtractor();

        $columns = $sut->extract(User::class);

        self::assertIsArray($columns);

        $expected = [
            ['id', 'integer'],
            ['username', 'string'],
            ['alias', 'string'],
            ['title', 'string'],
            ['email', 'string'],
            ['lastLogin', 'datetime'],
            ['language', 'string'],
            ['timezone', 'string'],
            ['active', 'boolean'],
            ['profile.registration_date', 'datetime'],
            ['roles', 'array'],
            ['color', 'string'],
            ['account_number', 'string'],
        ];

        self::assertCount(\count($expected), $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $i = 0;

        foreach ($expected as $item) {
            $column = $columns[$i++];
            self::assertEquals($item[0], $column->getLabel());
            self::assertEquals($item[1], $column->getType());
        }
    }

    public function testEqualsTo()
    {
        $sut = new User();
        $sut->setUserIdentifier('foo');

        $user = new TestUserEntity();
        self::assertFalse($sut->isEqualTo($user));

        $sut2 = clone $sut;
        self::assertTrue($sut->isEqualTo($sut));
        self::assertTrue($sut->isEqualTo($sut2));
        self::assertTrue($sut2->isEqualTo($sut));

        $sut->setPassword('sdfsdfsdfsdf');
        self::assertFalse($sut->isEqualTo($sut2));
        self::assertFalse($sut2->isEqualTo($sut));

        $sut2->setPassword('sdfsdfsdfsdf');
        self::assertTrue($sut->isEqualTo($sut2));
        self::assertTrue($sut2->isEqualTo($sut));

        $sut->setUserIdentifier('12345678');
        self::assertFalse($sut->isEqualTo($sut2));
        self::assertFalse($sut2->isEqualTo($sut));

        $sut2->setUserIdentifier('12345678');
        self::assertTrue($sut->isEqualTo($sut2));
        self::assertTrue($sut2->isEqualTo($sut));
    }

    public function testSerialize()
    {
        $sut = new User();
        $sut->setPassword('ABC-1234567890');
        $sut->setUserIdentifier('foo-BAR');
        $sut->setEmail('hello@world.com');
        $sut->setEnabled(false);

        $data = serialize($sut);

        $expected = [
            'foo-BAR',
            false,
            null,
            'hello@world.com',
        ];

        /** @var User $unserialized */
        $unserialized = unserialize($data);

        $actual = [
            $unserialized->getUserIdentifier(),
            $unserialized->isEnabled(),
            $unserialized->getId(),
            $unserialized->getEmail(),
        ];

        self::assertEquals($expected, $actual);
    }

    public function testTeamMemberships()
    {
        $team = new Team('Foo');

        $member = new TeamMember();
        $member->setTeam($team);

        $member2 = new TeamMember();
        $member2->setUser(new User());
        $member2->setTeam($team);

        $sut = new User();
        self::assertFalse($sut->isTeamleadOf($team));
        self::assertFalse($sut->isTeamlead());
        self::assertCount(0, $sut->getMemberships());
        self::assertFalse($sut->hasMembership($member));
        $sut->removeMembership($member);
        $sut->removeMembership($member2);
        $sut->addMembership($member);
        self::assertCount(1, $sut->getMemberships());
        $sut->removeMembership($member2);
        self::assertCount(1, $sut->getMemberships());
        $sut->removeMembership($member);
        self::assertCount(0, $sut->getMemberships());

        self::assertFalse($sut->isTeamleadOf($team));

        $member = new TeamMember();
        $member->setTeam($team);

        $sut->addMembership($member);
        self::assertCount(1, $sut->getMemberships());
        self::assertFalse($sut->isTeamleadOf($team));

        $member->setTeamlead(true);
        self::assertTrue($sut->isTeamleadOf($team));

        $member21 = new TeamMember();
        $member21->setTeam($team);

        self::assertNull($member21->getUser());
        // this will not be added, because $team is already assigned
        $sut->addMembership($member21);

        self::assertCount(1, $sut->getMemberships());
        self::assertSame($sut, $member21->getUser());

        $sut->addTeam(new Team('foo'));
        self::assertCount(2, $sut->getTeams());
        self::assertCount(2, $sut->getMemberships());

        $sut->removeTeam($team);
        self::assertCount(1, $sut->getMemberships());
    }

    public function testTeamMembershipsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $sut = new User();
        $member = new TeamMember();
        $member->setUser(new User());
        $sut->addMembership($member);
    }
}
