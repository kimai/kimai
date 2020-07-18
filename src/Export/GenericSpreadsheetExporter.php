<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Export\Annotation\Expose;
use App\Export\Annotation\Order;
use App\Export\CellFormatter\CellFormatterInterface;
use App\Export\CellFormatter\DateFormatter;
use App\Export\CellFormatter\DateTimeFormatter;
use App\Export\CellFormatter\DurationFormatter;
use App\Export\CellFormatter\TimeFormatter;
use Doctrine\Common\Annotations\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class GenericSpreadsheetExporter
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;
    /**
     * @var Reader
     */
    private $annotationReader;
    /**
     * @var CellFormatterInterface[]
     */
    private $formatter = [];

    public function __construct(TranslatorInterface $translator, Reader $annotationReader)
    {
        $this->translator = $translator;
        $this->annotationReader = $annotationReader;
        $this->registerCellFormatter('datetime', new DateTimeFormatter());
        $this->registerCellFormatter('date', new DateFormatter());
        $this->registerCellFormatter('time', new TimeFormatter());
        $this->registerCellFormatter('duration', new DurationFormatter());
        $this->expressionLanguage = $this->initExpressionLanguage();
    }

    public function registerCellFormatter(string $type, CellFormatterInterface $formatter)
    {
        $this->formatter[$type] = $formatter;
    }

    public function getExpressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguage;
    }

    private function initExpressionLanguage(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('implode'));

        return $expressionLanguage;
    }

    /**
     * @param string $class
     * @param array $entries
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(string $class, array $entries): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set default row height to automatic, so we can specify wrap text columns later on
        // without bloating the output file as we would need to store stylesheet info for every cell.
        // LibreOffice is still not considering this flag, @see https://github.com/PHPOffice/PHPExcel/issues/588
        // with no solution implemented so nothing we can do about it there.
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        $reflectionClass = new \ReflectionClass($class);
        $columns = [];

        if (null !== ($definitions = $this->annotationReader->getClassAnnotations($reflectionClass))) {
            foreach ($definitions as $definition) {
                if ($definition instanceof Order) {
                    foreach ($definition->order as $columnName) {
                        $columns[$columnName] = [];
                    }
                }
            }
            foreach ($definitions as $definition) {
                if ($definition instanceof Expose) {
                    if (null === $definition->name) {
                        throw new \Exception('@Expose needs a name attribute on class level hierarchy');
                    }
                    if (null === $definition->exp) {
                        throw new \Exception('@Expose needs an expression attribute on class level hierarchy');
                    }

                    $columns[$definition->name] = [
                        'accessor' => function ($obj) use ($definition) {
                            return $this->expressionLanguage->evaluate($definition->exp, ['object' => $obj]);
                        },
                        'label' => $definition->label,
                        'type' => $definition->type,
                    ];
                }
            }
        }

        foreach ($reflectionClass->getProperties() as $property) {
            if (null !== ($definitions = $this->annotationReader->getPropertyAnnotations($property))) {
                foreach ($definitions as $definition) {
                    if ($definition instanceof Expose) {
                        $name = empty($definition->name) ? $property->getName() : $definition->name;

                        $columns[$name] = [
                            'accessor' => function ($obj) use ($property) {
                                if (!$property->isPublic()) {
                                    $property->setAccessible(true);
                                }

                                return $property->getValue($obj);
                            },
                            'label' => $definition->label,
                            'type' => $definition->type,
                        ];
                    }
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            if (null !== ($definitions = $this->annotationReader->getMethodAnnotations($method))) {
                foreach ($definitions as $definition) {
                    if ($definition instanceof Expose) {
                        $name = empty($definition->name) ? $method->getName() : $definition->name;

                        if (\count($method->getParameters()) > 0) {
                            throw new \Exception(sprintf('@Expose does not support method %s::%s(...) as it needs parameter', $class, $method->getName()));
                        }

                        $columns[$name] = [
                            'accessor' => function ($obj) use ($method) {
                                if (!$method->isPublic()) {
                                    $method->setAccessible(true);
                                }

                                return $method->invoke($obj);
                            },
                            'label' => $definition->label,
                            'type' => $definition->type,
                        ];
                    }
                }
            }
        }

        foreach ($columns as $name => $settings) {
            if (empty($settings)) {
                unset($columns[$name]);
            }
        }

        $columns = array_values($columns);

        foreach ($columns as $settings) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($settings['label']));
        }

        $entryHeaderRow = $recordsHeaderRow + 1;

        foreach ($entries as $entry) {
            $entryHeaderColumn = 1;

            foreach ($columns as $settings) {
                $value = $settings['accessor']($entry);

                if (!\array_key_exists($settings['type'], $this->formatter)) {
                    $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $value);
                } else {
                    $formatter = $this->formatter[$settings['type']];
                    $formatter->setFormattedValue($sheet, $entryHeaderColumn, $entryHeaderRow, $value);
                }

                $entryHeaderColumn++;
            }

            $entryHeaderRow++;
        }

        return $spreadsheet;
    }
}
