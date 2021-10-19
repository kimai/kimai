<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Validator\Constraints\Team as TeamConstraint;
use App\Validator\Constraints\TeamValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\Team
 * @covers \App\Validator\Constraints\TeamValidator
 */
class TeamValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new TeamValidator();
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testMissingTeamlead()
    {
        $member = new TeamMember();
        $member->setTeamlead(false);
        $member->setUser(new User());

        $team = new Team();
        $team->addMember($member);

        $this->validator->validate($team, new TeamConstraint());

        $this->buildViolation('At least one team leader must be assigned to the team.')
            ->atPath('property.path.teamleads')
            ->setCode(TeamConstraint::MISSING_TEAMLEAD)
            ->assertRaised();
    }
}
