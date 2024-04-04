<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\ConfigLoaderInterface;
use App\Repository\CustomerRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\Customer as CustomerConstraint;
use App\Validator\Constraints\CustomerValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\Customer
 * @covers \App\Validator\Constraints\CustomerValidator
 * @extends ConstraintValidatorTestCase<CustomerValidator>
 */
class CustomerValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CustomerValidator
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, []);
        $repository = $this->createMock(CustomerRepository::class);

        return new CustomerValidator($config, $repository);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testGetTargets(): void
    {
        $constraint = new CustomerConstraint();
        self::assertEquals('class', $constraint->getTargets());
    }
}
