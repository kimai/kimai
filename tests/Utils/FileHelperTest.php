<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\FileHelper
 */
class FileHelperTest extends TestCase
{
    public static function getFileTestData()
    {
        return [
            ['Barss_laolala_ld_ksjf_123_MyAwesome_GmbH', 'Barß / laölala #   ld_ksjf 123 MyAwesome GmbH'],
            ['namaste', 'नमस्ते'],
            ['sa_yonara', 'さ!よなら'],
            ['sp_asibo_spa_sibo_spas_--_ibo', ' сп.асибо/спа   сибо#/!спас -- ибо!!'],
            ['kkakkaekkyakkyaekkeokke_kkyeokkyekkokkwasssss', '까깨꺄꺠꺼께_껴꼐꼬꽈sssss'],
            ['ss_n_-', '\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n"],
            ['Demo_ProjecT1', 'Demo ProjecT1'],
            ['kimai-export', 'kimai-export'],
            ['D_e_m_o_Pr_oj_e_c_T1', 'D"e&m%o# Pr\'oj\\e/c?T1'],
        ];
    }

    /**
     * @dataProvider getFileTestData
     */
    public function testEnsureMaxLength(string $expected, string $original): void
    {
        self::assertEquals($expected, FileHelper::convertToAsciiFilename($original));
    }

    public function testDataDirectory(): void
    {
        $data = realpath(__DIR__ . '/../_data/');
        $sut = new FileHelper($data);
        self::assertEquals($data . '/', $sut->getDataDirectory());
        self::assertEquals($data . '/foo/', $sut->getDataDirectory('/foo/'));
        self::assertEquals($data . '/foo/', $sut->getDataDirectory('foo'));

        $data = realpath(__DIR__ . '/../../var/data/');
        $sut->setDataDirectory($data);
        self::assertEquals($data . '/', $sut->getDataDirectory());
        self::assertEquals($data . '/foo/', $sut->getDataDirectory('/foo/'));
        self::assertEquals($data . '/foo/', $sut->getDataDirectory('foo'));
    }
}
