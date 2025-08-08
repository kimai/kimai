<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Calendar\IcsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IcalLinkValidator extends ConstraintValidator
{
    public function __construct(private IcsValidator $icsValidator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IcalLink) {
            throw new UnexpectedTypeException($constraint, IcalLink::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Check if it's a valid URL
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setCode(IcalLink::INVALID_URL)
                ->addViolation();
            return;
        }

        // Check if URL ends with .ics (optional but recommended)
        if (!str_ends_with(strtolower($value), '.ics')) {
            $this->context->buildViolation('The URL should end with .ics')
                ->setParameter('{{ value }}', $value)
                ->setCode(IcalLink::INVALID_ICS)
                ->addViolation();
        }

        // Validate URL accessibility and content
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Kimai/1.0',
                    'follow_location' => true,
                    'max_redirects' => 3,
                ]
            ]);

            // Get headers first to check content type and size
            $headers = get_headers($value, true, $context);
            if ($headers === false) {
                $this->context->buildViolation('Unable to access the URL')
                    ->setParameter('{{ value }}', $value)
                    ->setCode(IcalLink::DOWNLOAD_FAILED)
                    ->addViolation();
                return;
            }

            // Check content type if available
            $contentType = $headers['Content-Type'] ?? null;
            if ($contentType !== null) {
                $contentType = is_array($contentType) ? $contentType[0] : $contentType;
                if (!str_contains(strtolower($contentType), 'text/calendar') && 
                    !str_contains(strtolower($contentType), 'text/plain') &&
                    !str_contains(strtolower($contentType), 'application/octet-stream')) {
                    $this->context->buildViolation('The URL does not point to a valid calendar file')
                        ->setParameter('{{ value }}', $value)
                        ->setCode(IcalLink::INVALID_ICS_CONTENT)
                        ->addViolation();
                }
            }

            // Check content length if available
            $contentLength = $headers['Content-Length'] ?? null;
            if ($contentLength !== null) {
                $contentLength = is_array($contentLength) ? $contentLength[0] : $contentLength;
                if ((int) $contentLength > $constraint->maxFileSize) {
                    $this->context->buildViolation('The file is too large (maximum {{ max_size }} bytes)')
                        ->setParameter('{{ value }}', $value)
                        ->setParameter('{{ max_size }}', (string) $constraint->maxFileSize)
                        ->setCode(IcalLink::FILE_TOO_LARGE)
                        ->addViolation();
                    return;
                }
            }

            // Fetch and validate ICS content
            $content = file_get_contents($value, false, $context);
            if ($content === false) {
                $this->context->buildViolation('Unable to download the file')
                    ->setParameter('{{ value }}', $value)
                    ->setCode(IcalLink::DOWNLOAD_FAILED)
                    ->addViolation();
                return;
            }

            // Check actual file size
            if (strlen($content) > $constraint->maxFileSize) {
                $this->context->buildViolation('The file is too large (maximum {{ max_size }} bytes)')
                    ->setParameter('{{ value }}', $value)
                    ->setParameter('{{ max_size }}', (string) $constraint->maxFileSize)
                    ->setCode(IcalLink::FILE_TOO_LARGE)
                    ->addViolation();
                return;
            }

            // Validate ICS content structure
            if (!$this->icsValidator->isValidIcs($content)) {
                $this->context->buildViolation('The file does not contain valid ICS calendar data')
                    ->setParameter('{{ value }}', $value)
                    ->setCode(IcalLink::INVALID_ICS_CONTENT)
                    ->addViolation();
                return;
            }

        } catch (\Exception $e) {
            $this->context->buildViolation('Unable to validate the URL: {{ error }}')
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ error }}', $e->getMessage())
                ->setCode(IcalLink::DOWNLOAD_FAILED)
                ->addViolation();
        }
    }
} 