<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\LocaleService;
use App\Configuration\SystemConfiguration;
use App\Utils\LocaleFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Custom form field type to select the user locale, which is used to format date/time/money/number values.
 *
 * @extends AbstractType<string>
 */
final class UserLocaleType extends AbstractType
{
    /**
     * Fixed date used to render an example in the choice label: day > 12 makes the day/month order obvious,
     * the afternoon time reveals 12h vs 24h clocks.
     */
    private const EXAMPLE_DATE = '2025-12-24 14:30:00';
    /**
     * Large enough to show the thousands separator and the decimal sign.
     */
    private const EXAMPLE_AMOUNT = 1234.56;

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly LocaleService $localeService,
        private readonly SystemConfiguration $systemConfiguration
    )
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $route = $this->router->generate('help_locales');
        $moreLink = $this->translator->trans('help_locales');

        $resolver->setDefaults([
            'label' => 'locale',
            'help_html' => true,
            'help' => \sprintf('<a href="%1$s" target="help_locales">%2$s</a>', $route, $moreLink),
            // locales like "en" or "de" look correct for most users of that language, but the formatting
            // differs from the regional variant they are used to - showing example makes it visible
            'choice_label' => function (string $locale, string $name): string {
                $formatter = new LocaleFormatter($this->localeService, $locale);
                $example = new \DateTimeImmutable(self::EXAMPLE_DATE);

                return \sprintf(
                    '%s – %s, %s, %s',
                    $name,
                    $formatter->dateShort($example),
                    $formatter->time($example),
                    $formatter->money(self::EXAMPLE_AMOUNT, $this->systemConfiguration->getDefaultCurrency())
                );
            },
        ]);
    }

    public function getParent(): string
    {
        return LanguageType::class;
    }
}
