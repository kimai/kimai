<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Entity\User
 * @group integration
 */
class UserValidationTest extends KernelTestCase
{
    use EntityValidationTestTrait;

    public function getInvalidTestData()
    {
        return [
            ['', ''],
            [null, null],
            ['x', 'test@'], // too short username
            [str_pad('#', 61, '-'), 'test@x.'], // too long username
        ];
    }

    /**
     * @dataProvider getInvalidTestData
     */
    public function testInvalidValues($username, $email, $roles = [])
    {
        $defaultFields = [
            'username', 'email'
        ];

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        if (!empty($roles)) {
            $user->setRoles($roles);
            $defaultFields[] = 'roles';
        }

        $this->assertHasViolationForField($user, $defaultFields, ['Profile']);
    }

    public function testInvalidRoles()
    {
        $user = new User();
        $user->setUsername('foo');
        $user->setEmail('foo@example.com');
        $user->setRoles(['xxxxxx']);

        $this->assertHasViolationForField($user, ['roles'], ['RolesUpdate']);
    }

    public function testValidRoles()
    {
        $user = new User();
        $user->setUsername('foo');
        $user->setEmail('foo@example.com');
        $user->setRoles(['ROLE_TEAMLEAD']);

        $this->assertHasNoViolations($user, ['RolesUpdate']);
    }

    public function getValidTestData()
    {
        return [
            [str_pad('#', 8, '-'), 'test@x.x'], // shortest possible username
            [str_pad('#', 60, '-'), 'test@x.x'], // longest possible username
        ];
    }

    /**
     * @dataProvider getValidTestData
     */
    public function testValidValues($username, $email, $roles = [])
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        if (!empty($roles)) {
            $user->setRoles($roles);
        }

        $this->assertHasNoViolations($user, ['Profile']);
    }
}
