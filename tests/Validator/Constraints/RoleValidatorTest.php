<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\Role;
use App\Validator\Constraints\RoleValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @covers \App\Validator\Constraints\RoleValidator
 */
class RoleValidatorTest extends TestCase
{
    public function getValidRoles()
    {
        return [
            ['ROLE_CUSTOMER'],
            ['ROLE_USER'],
            ['ROLE_TEAMLEAD'],
            ['ROLE_ADMIN'],
            ['ROLE_SUPER_ADMIN'],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testConstraintIsInvalid()
    {
        $validator = new RoleValidator();
        $validator->validate('foo', new NotBlank());
    }

    /**
     * @dataProvider getValidRoles
     */
    public function testConstraintWithValidRole($role)
    {
        $constraint = new Role();
        $validator = new RoleValidator();
        $validator->validate($role, $constraint);
        // the above line would break if the role is invalid, we need the next assert to mark the test as valid
        $this->assertNull(null);
    }
}
