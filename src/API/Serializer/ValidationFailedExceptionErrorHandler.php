<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Serializer;

use App\Entity\User;
use App\Validator\ValidationFailedException;
use FOS\RestBundle\Serializer\Normalizer\FlattenExceptionHandler;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ValidationFailedExceptionErrorHandler implements SubscribingHandlerInterface
{
    public function __construct(private TranslatorInterface $translator, private FlattenExceptionHandler $exceptionHandler, private Security $security)
    {
    }

    public static function getSubscribingMethods(): array
    {
        return [[
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'type' => FlattenException::class,
            'format' => 'json',
            'method' => 'serializeExceptionToJson',
            'priority' => -1
        ], [
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'type' => ValidationFailedException::class,
            'format' => 'json',
            'method' => 'serializeValidationExceptionToJson',
            'priority' => -1
        ]];
    }

    public function serializeExceptionToJson(JsonSerializationVisitor $visitor, FlattenException $exception, array $type, Context $context)
    {
        if ($exception->getClass() !== ValidationFailedException::class) {
            return $this->exceptionHandler->serializeToJson($visitor, $exception, $type, $context);
        }

        $original = $context->getAttribute('exception');
        if ($original instanceof ValidationFailedException) {
            return $this->serializeValidationExceptionToJson($visitor, $original, $type, $context);
        }

        return $this->exceptionHandler->serializeToJson($visitor, $exception, $type, $context);
    }

    public function serializeValidationExceptionToJson(JsonSerializationVisitor $visitor, ValidationFailedException $exception, array $type, Context $context)
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
        $locale = \Locale::getDefault();
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user !== null) {
            $locale = $user->getLocale();
        }

        if (null !== $error->getPlural()) {
            return $this->translator->trans($error->getMessageTemplate(), ['%count%' => $error->getPlural()] + $error->getParameters(), 'validators', $locale);
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getParameters(), 'validators', $locale);
    }
}
