<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Repository\BookmarkRepository;
use App\Twig\DatatableExtensions;
use App\Utils\ProfileManager;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\DatatableExtensions
 */
class DatatableExtensionsTest extends TestCase
{
    protected function getSut(string $locale): DatatableExtensions
    {
        $repository = $this->createMock(BookmarkRepository::class);

        return new DatatableExtensions($repository, new ProfileManager());
    }

    public function testGetFunctions(): void
    {
        $functions = ['initialize_datatable', 'datatable_column_class'];
        $sut = $this->getSut('de');
        $twigFunctions = $sut->getFunctions();
        self::assertCount(\count($functions), $twigFunctions);
        $i = 0;
        foreach ($twigFunctions as $function) {
            self::assertInstanceOf(TwigFunction::class, $function);
            self::assertEquals($functions[$i++], $function->getName());
        }
    }
}
