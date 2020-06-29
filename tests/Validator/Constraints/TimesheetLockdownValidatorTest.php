<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Validator\Constraints\TimesheetLockdownValidator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetLockdownValidator
 */
class TimesheetLockdownValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturn(false);

        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = new TimesheetConfiguration($loader, [
            'rules' => [
                'allow_future_times' => true,
                'allow_overlapping_records' => true,
                // TODO add required configs for lockdown
            ],
            'rounding' => [
                'default' => [
                    'begin' => 1
                ]
            ]
        ]);

        return new TimesheetLockdownValidator($auth, $config);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    // TODO add some more assertions/tests here
}
