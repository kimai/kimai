<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\SystemConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for branding assets (logo, accent color).
 */
final class BrandingExtension extends AbstractExtension
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('branding_logo_url', [$this, 'logoUrl']),
            new TwigFunction('branding_logo_img', [$this, 'logoImg'], ['is_safe' => ['html']]),
            new TwigFunction('branding_accent_css', [$this, 'accentCss'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Returns the web-accessible URL for the stored logo.
     * If the config value is already a URL (backward compat), returns it as-is.
     */
    public function logoUrl(): ?string
    {
        $logo = $this->systemConfiguration->find('theme.branding.logo');

        if ($logo === null || $logo === '') {
            return null;
        }

        $logo = (string) $logo;

        // Backward compat: if it looks like a URL, return as-is
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '/')) {
            return $logo;
        }

        // It's a filename — generate the route URL
        return $this->urlGenerator->generate('branding_image', ['filename' => $logo], UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Returns an <img> tag for the logo (mPDF image var for PDF, URL for HTML).
     */
    public function logoImg(string $fallback = '', int $maxHeight = 30, bool $forPdf = true): string
    {
        $logo = $this->systemConfiguration->find('theme.branding.logo');

        if ($logo === null || $logo === '') {
            return htmlspecialchars($fallback, ENT_QUOTES);
        }

        $logo = (string) $logo;

        if ($forPdf) {
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
                $src = $logo;
            } else {
                $src = 'var:branding_logo';
            }
        } else {
            $src = $this->logoUrl();
            if ($src === null) {
                return htmlspecialchars($fallback, ENT_QUOTES);
            }
        }

        return \sprintf('<img src="%s" style="max-height: %dpx" alt="">', htmlspecialchars($src, ENT_QUOTES), $maxHeight);
    }

    /**
     * Returns CSS rules for the accent color, or empty string if not set.
     * @param array<string> $bgSelectors CSS selectors that get background-color + white text
     * @param array<string> $borderSelectors CSS selectors that get border-color
     */
    public function accentCss(array $bgSelectors = [], array $borderSelectors = []): string
    {
        $color = $this->systemConfiguration->find('theme.branding.accent_color');

        if ($color === null || $color === '') {
            return '';
        }

        $color = (string) $color;

        // Validate hex color to prevent CSS/HTML injection
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return '';
        }

        $rules = \sprintf(".accent { color: %s; }\n.accent-bg { background-color: %s; }\n.accent-border { border-color: %s; }\n", $color, $color, $color);

        foreach ($bgSelectors as $selector) {
            $rules .= \sprintf("%s { background-color: %s; color: #fff; }\n", $selector, $color);
        }

        foreach ($borderSelectors as $selector) {
            $rules .= \sprintf("%s { border-color: %s; }\n", $selector, $color);
        }

        return $rules;
    }
}
