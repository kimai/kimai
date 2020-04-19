<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\AvatarExtension;
use App\Utils\AvatarService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\AvatarExtension
 */
class AvatarExtensionTest extends TestCase
{
    protected function getSut(int $packagesGetUrlCount): AvatarExtension
    {
        $default = $this->getMockBuilder(PackageInterface::class)->getMock();
        $default->expects(self::exactly($packagesGetUrlCount))->method('getUrl')->willReturnCallback(function ($argument) {
            return 'http://www.example.com/' . $argument;
        });

        $service = $this->getMockBuilder(AvatarService::class)->disableOriginalConstructor()->getMock();
        $packages = new Packages();
        $packages->setDefaultPackage($default);

        return new AvatarExtension($service, $packages);
    }

    public function testGetFunctions()
    {
        $functions = ['avatar'];
        $sut = $this->getSut(0);
        $twigFunctions = $sut->getFunctions();
        self::assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            self::assertInstanceOf(TwigFunction::class, $filter);
            self::assertEquals($functions[$i++], $filter->getName());
        }
    }

    public function testGetAvatarWithoutUserReturnsDefault()
    {
        $sut = $this->getSut(1);

        self::assertEquals('http://www.example.com/blub', $sut->getAvatarUrl(null, 'blub'));
    }

    public function testGetAvatarWithUserProfileReturnsNullOnNullFromAvatarService()
    {
        $sut = $this->getSut(1);
        $user = new User();

        self::assertEquals('http://www.example.com/test', $sut->getAvatarUrl($user, 'test'));
    }
}
