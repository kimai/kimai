<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Tests\Mocks\SystemConfigurationFactory;
use App\Twig\BrandingExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;

#[CoversClass(BrandingExtension::class)]
class BrandingExtensionTest extends TestCase
{
    private function createExtension(array $settings = []): BrandingExtension
    {
        $config = SystemConfigurationFactory::createStub($settings);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/branding/image/test.png');

        return new BrandingExtension($config, $urlGenerator);
    }

    public function testGetFunctions(): void
    {
        $sut = $this->createExtension();
        $functions = $sut->getFunctions();
        self::assertCount(3, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('branding_logo_url', $functions[0]->getName());
        self::assertSame('branding_logo_img', $functions[1]->getName());
        self::assertSame('branding_accent_css', $functions[2]->getName());
    }

    public function testLogoUrlReturnsNullWhenNotConfigured(): void
    {
        $sut = $this->createExtension();
        self::assertNull($sut->logoUrl());
    }

    public function testLogoUrlReturnsUrlAsIs(): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['logo' => 'https://example.com/logo.png']]]);
        self::assertSame('https://example.com/logo.png', $sut->logoUrl());
    }

    public function testLogoUrlReturnsRouteForFilename(): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['logo' => 'mylogo.png']]]);
        self::assertSame('/branding/image/test.png', $sut->logoUrl());
    }

    public function testLogoImgReturnsFallbackWhenNoLogo(): void
    {
        $sut = $this->createExtension();
        self::assertSame('My Company', $sut->logoImg('My Company'));
    }

    public function testLogoImgEscapesFallback(): void
    {
        $sut = $this->createExtension();
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $sut->logoImg('<script>alert(1)</script>'));
    }

    public function testLogoImgReturnsImgTagForPdf(): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['logo' => 'mylogo.png']]]);
        $result = $sut->logoImg('Fallback', 40, true);
        self::assertStringContainsString('<img', $result);
        self::assertStringContainsString('var:branding_logo', $result);
        self::assertStringContainsString('max-height: 40px', $result);
    }

    public function testLogoImgReturnsImgTagForHtml(): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['logo' => 'mylogo.png']]]);
        $result = $sut->logoImg('Fallback', 30, false);
        self::assertStringContainsString('<img', $result);
        self::assertStringContainsString('/branding/image/test.png', $result);
    }

    public function testAccentCssReturnsEmptyWhenNotConfigured(): void
    {
        $sut = $this->createExtension();
        self::assertSame('', $sut->accentCss());
    }

    #[DataProvider('invalidColorProvider')]
    public function testAccentCssRejectsInvalidColors(string $color): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['accent_color' => $color]]]);
        self::assertSame('', $sut->accentCss());
    }

    public static function invalidColorProvider(): array
    {
        return [
            ['red'],
            ['#gggggg'],
            ['; body { background: red }'],
            ['#1234567'],
            [''],
        ];
    }

    #[DataProvider('validColorProvider')]
    public function testAccentCssAcceptsValidColors(string $color): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['accent_color' => $color]]]);
        $result = $sut->accentCss();
        self::assertStringContainsString($color, $result);
        self::assertStringContainsString('.accent', $result);
    }

    public static function validColorProvider(): array
    {
        return [
            ['#abc'],
            ['#AABBCC'],
            ['#ff0000'],
        ];
    }

    public function testAccentCssWithSelectors(): void
    {
        $sut = $this->createExtension(['theme' => ['branding' => ['accent_color' => '#206bc4']]]);
        $result = $sut->accentCss(['.header', '.footer'], ['.border-accent']);
        self::assertStringContainsString('.header { background-color: #206bc4; color: #fff; }', $result);
        self::assertStringContainsString('.footer { background-color: #206bc4; color: #fff; }', $result);
        self::assertStringContainsString('.border-accent { border-color: #206bc4; }', $result);
    }
}
