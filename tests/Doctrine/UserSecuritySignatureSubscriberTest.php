<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Doctrine;

use App\Doctrine\UserSecuritySignatureSubscriber;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Tests\KernelTestTrait;
use Doctrine\ORM\Events;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(UserSecuritySignatureSubscriber::class)]
#[Group('integration')]
class UserSecuritySignatureSubscriberTest extends KernelTestCase
{
    use KernelTestTrait;

    private function saveUser(User $user): void
    {
        // the User entity uses the DEFERRED_EXPLICIT change tracking policy
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Sets an old signature, so the reset can be detected.
     */
    private function setOldSignature(User $user): string
    {
        $reflection = new \ReflectionProperty(User::class, 'signatureDate');
        $reflection->setValue($user, new \DateTimeImmutable('2020-01-01 00:00:00'));
        $this->saveUser($user);

        return $user->getSignatureDate();
    }

    public function testGetSubscribedEvents(): void
    {
        $sut = new UserSecuritySignatureSubscriber();
        $events = $sut->getSubscribedEvents();
        self::assertTrue(\in_array(Events::onFlush, $events));
    }

    /**
     * @return iterable<string, array{0: callable(User): void}>
     */
    public static function getSecurityRelevantChanges(): iterable
    {
        yield 'email' => [function (User $user): void {
            $user->setEmail('changed@example.com');
        }];
        yield 'password' => [function (User $user): void {
            $user->setPassword('$2y$13$changedchangedchangedchangedchangedchangedchangedchangedchg');
        }];
        yield 'enable 2FA' => [function (User $user): void {
            $user->setTotpSecret('JBSWY3DPEHPK3PXP');
            $user->enableTotpAuthentication();
        }];
        yield 'disable user' => [function (User $user): void {
            $user->setEnabled(false);
        }];
    }

    #[DataProvider('getSecurityRelevantChanges')]
    public function testSecurityRelevantChangeResetsSignature(callable $change): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertEquals('', $user->getSignatureDate());

        $change($user);
        $this->saveUser($user);

        self::assertNotEquals('', $user->getSignatureDate());

        // make sure the new signature was persisted and is the same after loading it from the database
        $signature = $user->getSignatureDate();
        $this->getEntityManager()->clear();
        self::assertSame($signature, $this->getUserByRole(User::ROLE_USER)->getSignatureDate());
    }

    public function testDisableTwoFactorResetsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        $user->setTotpSecret('JBSWY3DPEHPK3PXP');
        $user->enableTotpAuthentication();
        $this->saveUser($user);

        // set an old signature, so the reset can be detected
        $reflection = new \ReflectionProperty(User::class, 'signatureDate');
        $reflection->setValue($user, new \DateTimeImmutable('2020-01-01 00:00:00'));
        $this->saveUser($user);
        $oldSignature = $user->getSignatureDate();

        $user->disableTotpAuthentication();
        $this->saveUser($user);

        self::assertNotEquals($oldSignature, $user->getSignatureDate());
    }

    public function testChangeTotpSecretOfActiveTwoFactorResetsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        $user->setTotpSecret('JBSWY3DPEHPK3PXP');
        $user->enableTotpAuthentication();
        $this->saveUser($user);

        $oldSignature = $this->setOldSignature($user);

        $user->setTotpSecret('KRSXG5CTMVRXEZLU');
        $this->saveUser($user);

        self::assertNotEquals($oldSignature, $user->getSignatureDate());
    }

    public function testDisableUserResetsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);

        // set an old signature, so the reset can be detected
        $reflection = new \ReflectionProperty(User::class, 'signatureDate');
        $reflection->setValue($user, new \DateTimeImmutable('2020-01-01 00:00:00'));
        $this->saveUser($user);
        $oldSignature = $user->getSignatureDate();

        $user->setEnabled(false);
        $this->saveUser($user);

        self::assertNotEquals($oldSignature, $user->getSignatureDate());
    }

    public function testIrrelevantChangeKeepsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertEquals('', $user->getSignatureDate());

        $user->setAlias('Foo Bar');
        $user->setTitle('Code Monkey');
        // generating a TOTP secret does not activate the 2FA (happens when opening the 2FA screen)
        $user->setTotpSecret('JBSWY3DPEHPK3PXP');
        $this->saveUser($user);

        self::assertEquals('', $user->getSignatureDate());
    }

    public function testRequirePasswordResetResetsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertNull($user->getPreference('__pw_reset__'));
        self::assertEquals('', $user->getSignatureDate());

        // the preference does not exist yet and is inserted
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);

        self::assertNotEquals('', $user->getSignatureDate());

        $signature = $user->getSignatureDate();
        $this->getEntityManager()->clear();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertTrue($user->requiresPasswordReset());
        self::assertSame($signature, $user->getSignatureDate());
    }

    public function testRequirePasswordResetOnExistingPreferenceResetsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        $user->setRequiresPasswordReset(false);
        $this->saveUser($user);
        self::assertInstanceOf(UserPreference::class, $user->getPreference('__pw_reset__'));
        $oldSignature = $this->setOldSignature($user);

        // only the preference is changed, the user itself has no changes
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);

        self::assertNotEquals($oldSignature, $user->getSignatureDate());

        $signature = $user->getSignatureDate();
        $this->getEntityManager()->clear();
        self::assertSame($signature, $this->getUserByRole(User::ROLE_USER)->getSignatureDate());
    }

    public function testRequirePasswordResetWithOtherSecurityChangeResetsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        $oldSignature = $this->setOldSignature($user);

        $user->setEmail('changed@example.com');
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);

        self::assertNotEquals($oldSignature, $user->getSignatureDate());
    }

    public function testRemovePasswordResetKeepsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);
        $oldSignature = $this->setOldSignature($user);

        $user->setRequiresPasswordReset(false);
        $this->saveUser($user);

        self::assertSame($oldSignature, $user->getSignatureDate());
    }

    public function testUnchangedPasswordResetKeepsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);
        $oldSignature = $this->setOldSignature($user);

        // e.g. an administrator saves the user form again, while the flag is still active
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);

        self::assertSame($oldSignature, $user->getSignatureDate());
    }

    public function testOtherPreferenceKeepsSignature(): void
    {
        self::bootKernel();
        $user = $this->getUserByRole(User::ROLE_USER);
        self::assertEquals('', $user->getSignatureDate());

        $user->setPreferenceValue('__wizards__', '1');
        $user->setPreferenceValue(UserPreference::SKIN, '1');
        $this->saveUser($user);

        self::assertEquals('', $user->getSignatureDate());
    }

    public function testNewUserWithPasswordResetCanBeCreated(): void
    {
        self::bootKernel();
        $user = new User();
        $user->setUserIdentifier('new_user_with_reset');
        $user->setEmail('new_user_with_reset@example.com');
        $user->setEnabled(true);
        $user->setPassword('$2y$13$changedchangedchangedchangedchangedchangedchangedchangedchg');
        $user->setRequiresPasswordReset(true);
        $this->saveUser($user);

        self::assertNotNull($user->getId());
        self::assertEquals('', $user->getSignatureDate());

        $this->getEntityManager()->clear();
        self::assertTrue($this->getUserByName('new_user_with_reset')->requiresPasswordReset());
    }
}
