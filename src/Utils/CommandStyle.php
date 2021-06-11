<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Validator\ValidationFailedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CommandStyle
{
    private $input;
    private $output;
    private $style;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    private function getStyle(): SymfonyStyle
    {
        if ($this->style === null) {
            $this->style = new SymfonyStyle($this->input, $this->output);
        }

        return $this->style;
    }

    public function success($message): void
    {
        $this->getStyle()->success($message);
    }

    public function error($message): void
    {
        $this->getStyle()->error($message);
    }

    public function warning($message): void
    {
        $this->getStyle()->warning($message);
    }

    public function validationError(ValidationFailedException $exception): void
    {
        $errors = $exception->getViolations();
        if ($errors->count() > 0) {
            $style = $this->getStyle();
            /** @var \Symfony\Component\Validator\ConstraintViolation $error */
            foreach ($errors as $error) {
                $value = $error->getInvalidValue();
                $style->error(
                    $error->getPropertyPath()
                    . ' (' . (\is_array($value) ? implode(',', $value) : $value) . ')'
                    . "\n    "
                    . $error->getMessage()
                );
            }
        }
    }
}
