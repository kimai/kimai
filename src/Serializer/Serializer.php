<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer as CoreSerializer;
use JMS\Serializer\SerializerInterface as CoreSerializerInterface;

class Serializer implements SerializerInterface
{
    public function __construct(private readonly CoreSerializerInterface $serializer)
    {
    }

    private function prepareContext(Context $jmsContext, array $context = []): void
    {
        $groups = ['Default'];
        if (\array_key_exists('groups', $context)) {
            $groups = $context['groups'];
        }

        $jmsContext->setGroups($groups);
        $jmsContext->enableMaxDepthChecks();
    }

    public function toArray(mixed $data, array $context = []): array
    {
        $jmsContext = SerializationContext::create();
        $this->prepareContext($jmsContext, $context);

        if ($this->serializer instanceof CoreSerializer) {
            return $this->serializer->toArray($data, $jmsContext);
        }

        $json = $this->serializer->serialize($data, 'json', $jmsContext);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function serialize(mixed $data, string $format, array $context = []): string
    {
        $jmsContext = SerializationContext::create();
        $this->prepareContext($jmsContext, $context);

        return $this->serializer->serialize($data, $format, $jmsContext);
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        $jmsContext = DeserializationContext::create();
        $this->prepareContext($jmsContext, $context);

        return $this->serializer->deserialize($data, $type, $format, $jmsContext);
    }
}
