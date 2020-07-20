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
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
final class AnnotationExtractor implements ExtractorInterface
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;
    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @param string $value
     * @return ColumnDefinition[]
     * @throws ExtractorException
     */
    public function extract($value): array
    {
        if (!\is_string($value)) {
            throw new ExtractorException('AnnotationExtractor needs a class name (string) for work');
        }

        try {
            $reflectionClass = new \ReflectionClass($value);
        } catch (\ReflectionException $ex) {
            throw new ExtractorException($ex->getMessage());
        }

        $columns = [];

        if (null !== ($definitions = $this->annotationReader->getClassAnnotations($reflectionClass))) {
            foreach ($definitions as $definition) {
                if ($definition instanceof Order) {
                    foreach ($definition->order as $columnName) {
                        $columns[$columnName] = null;
                    }
                }
            }
            foreach ($definitions as $definition) {
                if ($definition instanceof Expose) {
                    if (null === $definition->name) {
                        throw new ExtractorException('@Expose needs a name attribute on class level hierarchy');
                    }
                    if (null === $definition->exp) {
                        throw new ExtractorException('@Expose needs an expression attribute on class level hierarchy');
                    }

                    $parsed = $this->expressionLanguage->parse($definition->exp, ['object']);

                    $columns[$definition->name] = new ColumnDefinition(
                        $definition->label,
                        $definition->type,
                        function ($obj) use ($parsed) {
                            return $parsed->getNodes()->evaluate([], ['object' => $obj]);
                        }
                    );
                }
            }
        }

        foreach ($reflectionClass->getProperties() as $property) {
            if (null !== ($definitions = $this->annotationReader->getPropertyAnnotations($property))) {
                foreach ($definitions as $definition) {
                    if ($definition instanceof Expose) {
                        if (null !== $definition->exp) {
                            throw new ExtractorException('@Expose only supports the expression attribute on class level hierarchy');
                        }

                        $name = empty($definition->name) ? $property->getName() : $definition->name;

                        $columns[$name] = new ColumnDefinition(
                            $definition->label,
                            $definition->type,
                            function ($obj) use ($property) {
                                if (!$property->isPublic()) {
                                    $property->setAccessible(true);
                                }

                                return $property->getValue($obj);
                            }
                        );
                    }
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            if (null !== ($definitions = $this->annotationReader->getMethodAnnotations($method))) {
                foreach ($definitions as $definition) {
                    if ($definition instanceof Expose) {
                        if (null !== $definition->exp) {
                            throw new ExtractorException('@Expose only supports the expression attribute on class level hierarchy');
                        }
                        $name = empty($definition->name) ? $method->getName() : $definition->name;

                        if (\count($method->getParameters()) > 0) {
                            throw new ExtractorException(sprintf('@Expose does not support method %s::%s(...) as it needs parameter', $value, $method->getName()));
                        }

                        $columns[$name] = new ColumnDefinition(
                            $definition->label,
                            $definition->type,
                            function ($obj) use ($method) {
                                if (!$method->isPublic()) {
                                    $method->setAccessible(true);
                                }

                                return $method->invoke($obj);
                            }
                        );
                    }
                }
            }
        }

        foreach ($columns as $name => $defintion) {
            if (null === $defintion) {
                unset($columns[$name]);
            }
        }

        return array_values($columns);
    }
}
