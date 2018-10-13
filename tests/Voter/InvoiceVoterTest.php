<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\InvoiceTemplate;
use App\Entity\User;
use App\Security\AclDecisionManager;
use App\Voter\InvoiceVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\InvoiceVoter
 */
class InvoiceVoterTest extends TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $isAuthenticated, $hasRole, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());

        $accessManager = $this->getMockBuilder(AclDecisionManager::class)->disableOriginalConstructor()->getMock();
        $accessManager->method('isFullyAuthenticated')->willReturn($isAuthenticated);
        $accessManager->method('hasRole')->willReturn($hasRole);

        $sut = new InvoiceVoter($accessManager);

        $this->assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, User::ROLE_CUSTOMER);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(1, User::ROLE_TEAMLEAD);
        $user3 = $this->getUser(1, User::ROLE_ADMIN);
        $user4 = $this->getUser(1, User::ROLE_SUPER_ADMIN);

        $users = [$user0, $user1, $user2, $user3, $user4];
        $attributes = [InvoiceVoter::VIEW, InvoiceVoter::CREATE, InvoiceVoter::EDIT, InvoiceVoter::DELETE];
        $subjects = ['invoice', 'invoice_template', new InvoiceTemplate()];

        foreach ($attributes as $attribute) {
            foreach ($users as $user) {
                foreach ($subjects as $subject) {
                    yield [$user, false, false, $subject, $attribute, VoterInterface::ACCESS_DENIED];
                    yield [$user, true, false, $subject, $attribute, VoterInterface::ACCESS_DENIED];
                    yield [$user, false, true, $subject, $attribute, VoterInterface::ACCESS_DENIED];
                    yield [$user, true, true, $subject, $attribute, VoterInterface::ACCESS_GRANTED];
                    yield [$user, true, true, $subject, 'something', VoterInterface::ACCESS_ABSTAIN];
                }
                yield [$user, true, true, new \stdClass(), $attribute, VoterInterface::ACCESS_ABSTAIN];
                yield [$user, false, true, new \stdClass(), $attribute, VoterInterface::ACCESS_ABSTAIN];
                yield [$user, true, false, new \stdClass(), $attribute, VoterInterface::ACCESS_ABSTAIN];
                yield [$user, false, false, new \stdClass(), $attribute, VoterInterface::ACCESS_ABSTAIN];
                yield [$user, true, true, null, $attribute, VoterInterface::ACCESS_ABSTAIN];
                yield [$user, true, true, 'foo', $attribute, VoterInterface::ACCESS_ABSTAIN];
            }
        }
    }

    protected function getUser($id, $role)
    {
        $user = $this->getMockBuilder(User::class)->getMock();
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn([$role]);

        return $user;
    }
}
