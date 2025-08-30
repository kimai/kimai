<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\Extractor;

use App\Export\Annotation\Expose;
use App\Export\Annotation\Order;
use App\Export\Spreadsheet\ColumnDefinition;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
final class AnnotationExtractor implements ExtractorInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @param string $value
     * @return list<ColumnDefinition>
     * @throws ExtractorException
     */
    public function extract($value): array
    {
        if (!\is_string($value) || empty($value)) {
            throw new ExtractorException('AnnotationExtractor needs a non-empty class name for work');
        }

        try {
            $reflectionClass = new \ReflectionClass($value);
        } catch (\ReflectionException $ex) {
            throw new ExtractorException($ex->getMessage());
        }

        $columns = [];

        $order = $reflectionClass->getAttributes(Order::class);
        foreach ($order as $definition) {
            foreach ($definition->getArguments()[0] as $columnName) {
                $columns[$columnName] = null;
            }
        }

        $definitions = $reflectionClass->getAttributes(Expose::class);
        foreach ($definitions as $definition) {
            $arguments = $definition->getArguments();
            if (!\array_key_exists('name', $arguments) || $arguments['name'] === null) {
                throw new ExtractorException(\sprintf('@Expose needs the "name" attribute on class level hierarchy, check %s::class', $value));
            }
            if (!\array_key_exists('exp', $arguments) || $arguments['exp'] === null) {
                throw new ExtractorException(\sprintf('@Expose needs the "exp" attribute on class level hierarchy, check %s::class', $value));
            }
            if (!\array_key_exists('label', $arguments) || $arguments['label'] === null) {
                throw new ExtractorException(\sprintf('@Expose needs the "label" attribute on class level hierarchy, check %s::class', $value));
            }

            $parsed = $this->expressionLanguage->parse($arguments['exp'], ['object']);

            $name = $arguments['name'];

            $columns[$name] = new ColumnDefinition(
                $arguments['label'],
                $arguments['type'] ?? 'string',
                function ($obj) use ($parsed) {
                    return $parsed->getNodes()->evaluate([], ['object' => $obj]);
                }
            );

            if (\array_key_exists('translationDomain', $arguments) && \is_string($arguments['translationDomain'])) {
                $columns[$name]->setTranslationDomain($arguments['translationDomain']);
            }
        }

        foreach ($reflectionClass->getProperties() as $property) {
            $definitions = $property->getAttributes(Expose::class);
            foreach ($definitions as $definition) {
                $arguments = $definition->getArguments();
                if (\array_key_exists('exp', $arguments) && $arguments['exp'] !== null) {
                    throw new ExtractorException(\sprintf('@Expose only supports the "exp" attribute on class level hierarchy, check %s::$%s', $value, $property->getName()));
                }
                if (!\array_key_exists('label', $arguments) || $arguments['label'] === null) {
                    throw new ExtractorException(\sprintf('@Expose needs the "label" attribute on property level hierarchy, check %s::$%s', $value, $property->getName()));
                }

                $name = $property->getName();
                if (\array_key_exists('name', $arguments) && $arguments['name'] !== null) {
                    $name = $arguments['name'];
                }

                $columns[$name] = new ColumnDefinition(
                    $arguments['label'],
                    $arguments['type'] ?? 'string',
                    function ($obj) use ($property) {
                        if (!$property->isPublic()) {
                            $property->setAccessible(true);
                        }

                        return $property->getValue($obj);
                    }
                );

                if (\array_key_exists('translationDomain', $arguments) && \is_string($arguments['translationDomain'])) {
                    $columns[$name]->setTranslationDomain($arguments['translationDomain']);
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            $definitions = $method->getAttributes(Expose::class);
            foreach ($definitions as $definition) {
                if (\count($method->getParameters()) > 0) {
                    throw new ExtractorException(\sprintf('@Expose does not support method %s::%s(...) as it has required parameters.', $value, $method->getName()));
                }

                $arguments = $definition->getArguments();
                if (\array_key_exists('exp', $arguments) && $arguments['exp'] !== null) {
                    throw new ExtractorException(\sprintf('@Expose only supports the "exp" attribute on method level hierarchy, check %s::%s()', $value, $method->getName()));
                }
                if (!\array_key_exists('label', $arguments) || $arguments['label'] === null) {
                    throw new ExtractorException(\sprintf('@Expose needs the "label" attribute on method level hierarchy, check %s::%s()', $value, $method->getName()));
                }

                $name = $method->getName();
                if (\array_key_exists('name', $arguments) && $arguments['name'] !== null) {
                    $name = $arguments['name'];
                }

                $columns[$name] = new ColumnDefinition(
                    $arguments['label'],
                    $arguments['type'] ?? 'string',
                    function ($obj) use ($method) {
                        if (!$method->isPublic()) {
                            $method->setAccessible(true);
                        }

                        return $method->invoke($obj);
                    }
                );

                if (\array_key_exists('translationDomain', $arguments) && \is_string($arguments['translationDomain'])) {
                    $columns[$name]->setTranslationDomain($arguments['translationDomain']);
                }
            }
        }

        $columns = array_filter($columns, function ($value) { return $value !== null; });

        return array_values($columns);
    }
}
