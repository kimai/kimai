<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Invoice;
use App\Entity\User;
use App\Voter\EnvironmentVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(EnvironmentVoter::class)]
class EnvironmentVoterTest extends AbstractVoterTestCase
{
    public function testSupportsAttribute(): void
    {
        $sut = $this->createVoter();

        self::assertTrue($sut->supportsAttribute('upload_invoice_template'));
        self::assertFalse($sut->supportsAttribute('manage_invoice_template'));
        self::assertFalse($sut->supportsAttribute('view_invoice'));
        self::assertFalse($sut->supportsAttribute(''));
    }

    public function testSupportsType(): void
    {
        $sut = $this->createVoter();

        self::assertTrue($sut->supportsType('null'));
        self::assertFalse($sut->supportsType(Invoice::class));
        self::assertFalse($sut->supportsType('string'));
    }

    #[DataProvider('getAbstainData')]
    public function testVoteAbstains(mixed $subject, string $attribute): void
    {
        $user = self::getUser(1, User::ROLE_SUPER_ADMIN);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $this->vote($user, $subject, $attribute));
    }

    public static function getAbstainData(): \Generator
    {
        yield [null, 'manage_invoice_template'];
        yield [null, 'view_invoice'];
        yield [new Invoice(), 'upload_invoice_template'];
        yield [new \stdClass(), 'upload_invoice_template'];
        yield ['', 'upload_invoice_template'];
    }

    public function testVoteDeniesIfTokenHasNoApplicationUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $this->createVoter()->vote($token, null, ['upload_invoice_template']));
    }

    #[DataProvider('getUsersWithoutPermission')]
    public function testVoteDeniesWithoutRolePermission(User $user): void
    {
        self::assertEquals(VoterInterface::ACCESS_DENIED, $this->vote($user, null, 'upload_invoice_template'));
    }

    public static function getUsersWithoutPermission(): \Generator
    {
        yield [self::getUser(0, 'foo')];
        yield [self::getUser(1, User::ROLE_USER)];
        yield [self::getUser(2, User::ROLE_TEAMLEAD)];
        yield [self::getUser(3, User::ROLE_ADMIN)];
    }

    public function testVoteWithRolePermissionDependsOnFileUploads(): void
    {
        $user = self::getUser(4, User::ROLE_SUPER_ADMIN);

        // "file_uploads" is a PHP_INI_SYSTEM setting and cannot be changed at runtime,
        // run this test with "php -d file_uploads=0" to cover the denied branch
        $expected = filter_var(\ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN) ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;

        self::assertEquals($expected, $this->vote($user, null, 'upload_invoice_template'));
    }

    private function vote(User $user, mixed $subject, string $attribute): int
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());

        return $this->createVoter()->vote($token, $subject, [$attribute]);
    }

    private function createVoter(): EnvironmentVoter
    {
        return new EnvironmentVoter($this->getRolePermissionManager([
            'ROLE_ADMIN' => ['manage_invoice_template', 'view_invoice'],
            'ROLE_SUPER_ADMIN' => ['manage_invoice_template', 'view_invoice', 'upload_invoice_template'],
        ], true));
    }
}
