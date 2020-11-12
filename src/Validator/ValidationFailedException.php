<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationFailedException extends \RuntimeException
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $violations;

    public function __construct(ConstraintViolationListInterface $violations, ?string $message = null)
    {
        if ($message === null) {
            $message = 'Validation failed';
        }
        parent::__construct($message, 400);

        $this->violations = $violations;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
