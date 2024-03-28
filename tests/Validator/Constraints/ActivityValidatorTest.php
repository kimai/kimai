<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\ConfigLoaderInterface;
use App\Repository\ActivityRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\Activity as ActivityConstraint;
use App\Validator\Constraints\ActivityValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\Activity
 * @covers \App\Validator\Constraints\ActivityValidator
 * @extends ConstraintValidatorTestCase<ActivityValidator>
 */
class ActivityValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ActivityValidator
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, []);
        $repository = $this->createMock(ActivityRepository::class);

        return new ActivityValidator($config, $repository);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testGetTargets(): void
    {
        $constraint = new ActivityConstraint();
        self::assertEquals('class', $constraint->getTargets());
    }
}
