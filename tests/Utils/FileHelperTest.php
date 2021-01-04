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
    public function getFileTestData()
    {
        return [
            ['barss_laolala_ldksjf123_my_awesome_gmb_h', 'barß / laölala #   ldksjf 123 MyAwesome GmbH'],
            ['namaste', 'नमस्ते'],
            ['sa_yonara', 'さ!よなら'],
            ['sp_asibo_spa_sibo_spas_ibo', ' сп.асибо/спа   сибо#/!спас -- ибо!!'],
            ['kkakkaekkyakkyaekkeokkekkyeokkyekkokkwasssss', '까깨꺄꺠꺼께껴꼐꼬꽈sssss'],
            ['ss_n', '\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n"],
            ['demo_projec_t1', 'Demo ProjecT1'],
            ['demo_pr_oj_ect1', 'D"e&m%o# Pr\'oj\\e/c?T1'],
        ];
    }

    /**
     * @dataProvider getFileTestData
     */
    public function testEnsureMaxLength(string $expected, string $original)
    {
        self::assertEquals($expected, FileHelper::convertToAsciiFilename($original));
    }
}
