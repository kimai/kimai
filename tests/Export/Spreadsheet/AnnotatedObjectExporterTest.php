<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Entity\Customer;
use App\Entity\Project;
use App\Export\Spreadsheet\AnnotatedObjectExporter;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\SpreadsheetExporter;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Spreadsheet\AnnotatedObjectExporter
 */
class AnnotatedObjectExporterTest extends TestCase
{
    public function testExport()
    {
        $spreadsheetExporter = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $annotationExtractor = new AnnotationExtractor(new AnnotationReader());

        $project = new Project();
        $project->setName('test project');
        $project->setCustomer((new Customer())->setName('A customer'));
        $project->setComment('Lorem Ipsum');
        $project->setOrderNumber('1234567890');
        $project->setBudget(123456.7890);
        $project->setTimeBudget(1234567890);
        $project->setColor('#ababab');
        $project->setVisible(false);

        $sut = new AnnotatedObjectExporter($spreadsheetExporter, $annotationExtractor);
        $spreadsheet = $sut->export(Project::class, [$project]);
        $worksheet = $spreadsheet->getActiveSheet();

        self::assertNull($worksheet->getCellByColumnAndRow(1, 2, false)->getValue());
        self::assertEquals('test project', $worksheet->getCellByColumnAndRow(2, 2, false)->getValue());
        self::assertEquals('A customer', $worksheet->getCellByColumnAndRow(3, 2, false)->getValue());
        self::assertEquals(1234567890, $worksheet->getCellByColumnAndRow(4, 2, false)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(5, 2, false)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(6, 2, false)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(7, 2, false)->getValue());
        self::assertEquals('#ababab', $worksheet->getCellByColumnAndRow(8, 2, false)->getValue());
        self::assertFalse($worksheet->getCellByColumnAndRow(9, 2, false)->getValue());
        self::assertEquals('Lorem Ipsum', $worksheet->getCellByColumnAndRow(10, 2, false)->getValue());
    }
}
