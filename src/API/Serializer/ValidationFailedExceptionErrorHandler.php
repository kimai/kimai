<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Serializer;

use App\Validator\ValidationFailedException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidationFailedExceptionErrorHandler implements SubscribingHandlerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function getSubscribingMethods()
    {
        return [[
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'type' => ValidationFailedException::class,
            'format' => 'json',
            'method' => 'serializeExceptionToJson',
        ]];
    }

    public function serializeExceptionToJson(SerializationVisitorInterface $visitor, ValidationFailedException $exception, array $type)
    {
        $errors = [];
        /** @var ConstraintViolationInterface $error */
        foreach (iterator_to_array($exception->getViolations()) as $error) {
            $errors[$error->getPropertyPath()]['errors'][] = $this->getErrorMessage($error);
        }

        return [
            'code' => '400',
            'message' => $this->translator->trans($exception->getMessage(), [], 'validators'),
            'errors' => [
                'children' => $errors
            ],
        ];
    }

    private function getErrorMessage(ConstraintViolationInterface $error): string
    {
        if (null !== $error->getPlural()) {
            return $this->translator->trans($error->getMessageTemplate(), ['%count%' => $error->getPlural()] + $error->getParameters(), 'validators');
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getParameters(), 'validators');
    }
}
