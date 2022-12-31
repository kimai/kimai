<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Entity\User;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\UserPreferenceExtractor;
use App\Export\Spreadsheet\SpreadsheetExporter;
use App\Export\Spreadsheet\UserExporter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Spreadsheet\UserExporter
 */
class UserExporterTest extends TestCase
{
    public function testExport()
    {
        $spreadsheetExporter = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $annotationExtractor = new AnnotationExtractor();
        $userPreferenceExtractor = new UserPreferenceExtractor($this->createMock(EventDispatcherInterface::class));

        $user = new User();
        $user->setAccountNumber('F-747864');
        $user->setUserIdentifier('test user');
        $user->setAvatar('Lorem Ipsum');
        $user->setTimezone('Europe/Berlin');
        $user->setColor('#ececec');
        $user->setAlias('Another name');
        $user->setTitle('Mr. Title');
        $user->setLanguage('de');
        $user->setEmail('test@example.com');
        $user->setEnabled(false);
        $user->addRole(User::ROLE_TEAMLEAD);
        $date = $user->getRegisteredAt();

        $sut = new UserExporter($spreadsheetExporter, $annotationExtractor, $userPreferenceExtractor);
        $spreadsheet = $sut->export([$user], new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT));
        $worksheet = $spreadsheet->getActiveSheet();

        self::assertNull($worksheet->getCellByColumnAndRow(1, 2)->getValue());
        self::assertEquals('test user', $worksheet->getCellByColumnAndRow(2, 2)->getValue());
        self::assertEquals('Another name', $worksheet->getCellByColumnAndRow(3, 2)->getValue());
        self::assertEquals('Mr. Title', $worksheet->getCellByColumnAndRow(4, 2)->getValue());
        self::assertEquals('test@example.com', $worksheet->getCellByColumnAndRow(5, 2)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(6, 2)->getValue());
        self::assertEquals('de', $worksheet->getCellByColumnAndRow(7, 2)->getValue());
        self::assertEquals('Europe/Berlin', $worksheet->getCellByColumnAndRow(8, 2)->getValue());
        self::assertFalse($worksheet->getCellByColumnAndRow(9, 2)->getValue());
        self::assertEquals($date->format('Y-m-d H:i'), $worksheet->getCellByColumnAndRow(10, 2)->getFormattedValue());
        self::assertEquals('ROLE_TEAMLEAD;ROLE_USER', $worksheet->getCellByColumnAndRow(11, 2)->getValue());
        self::assertEquals('#ececec', $worksheet->getCellByColumnAndRow(12, 2)->getValue());
        self::assertEquals('F-747864', $worksheet->getCellByColumnAndRow(13, 2)->getValue());
    }
}
