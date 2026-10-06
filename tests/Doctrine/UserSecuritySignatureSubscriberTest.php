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
}
