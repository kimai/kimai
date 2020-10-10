<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Importer;

use App\Importer\CsvReader;
use App\Importer\ImportNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\CsvReader
 */
class CsvReaderTest extends TestCase
{
    public function testRead()
    {
        $sut = new CsvReader(';');
        $result = $sut->read(__DIR__ . '/_data/grandtotal_en.csv');
        $result = iterator_to_array($result);
        self::assertEquals([1 => [
            'Organization' => 'Keleo',
            'Department' => 'IT Abteilung',
            'Title' => 'Bc',
            'First name' => 'Kevin',
            'Middle name' => '',
            'Last name' => 'Papst',
            'E-Mail' => 'unknown@kimai.org',
            'Street' => 'Acme Street
Downtown',
            'ZIP' => '1022',
            'City' => 'Vienna',
            'State' => '',
            'Salutation' => 'Sehr geehrter Herr',
            'Country' => 'DE',
            'Customer number' => '00001',
            'Tax-ID' => 'DE1234567890',
            'Note' => 'sakdjhfg laksjhdfasd f#asd<br>
 fas<br>
 dfasdfasdf<br>
 asdfasdfasdf',
            'IBAN' => '0987654321',
            'BIC' => '12345678',
            'SEPA Mandate ID' => '',
            'zusatz 1' => 'blub',
            'zusatz 2' => 'foo',
          ]], $result);
    }

    public function testReadNotFound()
    {
        $this->expectException(ImportNotFoundException::class);

        $sut = new CsvReader(';');
        $sut->read(__DIR__ . '/_data/fffffoooooooooooo');
    }
}
