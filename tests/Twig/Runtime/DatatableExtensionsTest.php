<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Repository\BookmarkRepository;
use App\Twig\Runtime\DatatableExtensions;
use App\Utils\ProfileManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(DatatableExtensions::class)]
class DatatableExtensionsTest extends TestCase
{
    protected function getSut(): DatatableExtensions
    {
        $repository = $this->createMock(BookmarkRepository::class);

        return new DatatableExtensions($repository, new ProfileManager(), new RequestStack());
    }

    public function testGetFunctions(): void
    {
        $functions = ['initializeDatatable', 'getDatatableColumnClass'];
        $sut = $this->getSut();
        foreach ($functions as $function) {
            self::assertTrue(method_exists($sut, $function), 'Failed finding method: ' . $function);
        }
    }
}
