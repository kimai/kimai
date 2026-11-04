<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Team;
use App\Entity\User;
use App\Voter\InvoiceVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(InvoiceVoter::class)]
class InvoiceVoterTest extends AbstractVoterTestCase
{
    #[DataProvider('getVoteData')]
    public function testVote(User $user, mixed $subject, string $attribute, int $result): void
    {
        $this->assertVote($user, $subject, $attribute, $result);
    }

    public function testVoteDeniesIfTokenHasNoApplicationUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $sut = $this->getVoter(InvoiceVoter::class);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $sut->vote($token, $this->createInvoice(), ['view_invoice']));
    }

    public function testVoteDeniesIfInvoiceHasNoCustomer(): void
    {
        $this->assertVote(self::getUser(2, User::ROLE_TEAMLEAD), new Invoice(), 'view_invoice', VoterInterface::ACCESS_DENIED);
    }

    public function testVoteRequiresCustomerTeamAccess(): void
    {
        $customer = new Customer('Acme');
        $customer->addTeam(new Team('Accounting'));
        $invoice = $this->createInvoice($customer);

        $this->assertVote(self::getUser(2, User::ROLE_TEAMLEAD), $invoice, 'view_invoice', VoterInterface::ACCESS_DENIED);

        $team = new Team('Accounting');
        $user = new User();
        $user->addRole(User::ROLE_TEAMLEAD);
        $team->addTeamlead($user);

        $customer = new Customer('Acme');
        $customer->addTeam($team);
        $invoice = $this->createInvoice($customer);

        $this->assertVote($user, $invoice, 'view_invoice', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($user, $invoice, 'edit_invoice', VoterInterface::ACCESS_GRANTED);
    }

    public function testDeleteInvoiceRequiresDeletePermission(): void
    {
        $permissions = [
            'ROLE_TEAMLEAD' => ['view_invoice', 'create_invoice', 'delete_invoice'],
        ];

        $team = new Team('Accounting');
        $user = new User();
        $user->addRole(User::ROLE_TEAMLEAD);
        $team->addTeamlead($user);

        $customer = new Customer('Acme');
        $customer->addTeam($team);

        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = new InvoiceVoter($this->getRolePermissionManager($permissions, true));

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $sut->vote($token, $this->createInvoice($customer), ['delete_invoice']));
    }

    public static function getVoteData(): \Generator
    {
        $invoice = self::createStaticInvoice();

        yield [self::getUser(0, 'foo'), $invoice, 'view_invoice', VoterInterface::ACCESS_DENIED];
        yield [self::getUser(1, User::ROLE_USER), $invoice, 'view_invoice', VoterInterface::ACCESS_DENIED];
        yield [self::getUser(2, User::ROLE_TEAMLEAD), $invoice, 'view_invoice', VoterInterface::ACCESS_GRANTED];
        yield [self::getUser(2, User::ROLE_TEAMLEAD), $invoice, 'edit_invoice', VoterInterface::ACCESS_GRANTED];
        yield [self::getUser(2, User::ROLE_TEAMLEAD), $invoice, 'delete_invoice', VoterInterface::ACCESS_DENIED];
        yield [self::getUser(3, User::ROLE_ADMIN), $invoice, 'view_invoice', VoterInterface::ACCESS_GRANTED];
        yield [self::getUser(3, User::ROLE_ADMIN), $invoice, 'edit_invoice', VoterInterface::ACCESS_GRANTED];
        yield [self::getUser(3, User::ROLE_ADMIN), $invoice, 'delete_invoice', VoterInterface::ACCESS_DENIED];
        yield [self::getUser(4, User::ROLE_SUPER_ADMIN), $invoice, 'view_invoice', VoterInterface::ACCESS_GRANTED];
        yield [self::getUser(4, User::ROLE_SUPER_ADMIN), $invoice, 'edit_invoice', VoterInterface::ACCESS_GRANTED];
        yield [self::getUser(4, User::ROLE_SUPER_ADMIN), $invoice, 'delete_invoice', VoterInterface::ACCESS_DENIED];

        $result = VoterInterface::ACCESS_ABSTAIN;
        yield [self::getUser(2, User::ROLE_TEAMLEAD), $invoice, 'view', $result];
        yield [self::getUser(2, User::ROLE_TEAMLEAD), new \stdClass(), 'view_invoice', $result];
        yield [self::getUser(2, User::ROLE_TEAMLEAD), null, 'edit_invoice', $result];
    }

    private function assertVote(User $user, mixed $subject, string $attribute, int $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(InvoiceVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    private function createInvoice(?Customer $customer = null): Invoice
    {
        $invoice = new Invoice();
        if ($customer !== null) {
            $invoice->setCustomer($customer);
        }

        return $invoice;
    }

    private static function createStaticInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setCustomer(new Customer('Acme'));

        return $invoice;
    }
}
