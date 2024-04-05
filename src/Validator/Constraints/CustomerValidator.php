<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer as CustomerEntity;
use App\Repository\CustomerRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class CustomerValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly CustomerRepository $customerRepository
    )
    {
    }

    /**
     * @param CustomerEntity|mixed $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof Customer)) {
            throw new UnexpectedTypeException($constraint, Customer::class);
        }

        if (!($value instanceof CustomerEntity)) {
            throw new UnexpectedTypeException($value, CustomerEntity::class);
        }

        if ((bool) $this->systemConfiguration->find('customer.rules.allow_duplicate_number') === false && (($number = $value->getNumber()) !== null)) {
            foreach ($this->customerRepository->findBy(['number' => $number]) as $tmp) {
                if ($tmp->getId() !== $value->getId()) {
                    $this->context->buildViolation(Customer::getErrorName(Customer::CUSTOMER_NUMBER_EXISTING))
                        ->setParameter('%number%', $number)
                        ->atPath('number')
                        ->setTranslationDomain('validators')
                        ->setCode(Customer::CUSTOMER_NUMBER_EXISTING)
                        ->addViolation();
                    break;
                }
            }
        }
    }
}
