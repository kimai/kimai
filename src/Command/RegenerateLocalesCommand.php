<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\LocaleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Locales;

/**
 * Command used to create the locale definition.
 *
 * We do NOT calculate that on every system again, because we want to make sure that we have the same
 * settings in every environment. Some environments (e.g. Github-Actions) have diverging settings from
 * the local system.
 *
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:reset:locales', description: 'Regenerate the locale definition file')]
final class RegenerateLocalesCommand extends Command
{
    /**
     * @var string[]
     */
    private array $rtlLocales = ['ar', 'fa', 'he'];
    /**
     * new locales were added here, to shrink the list a little bit
     * this can be removed in the future, if there will ever be the need for it
     *
     * @var string[]
     */
    private array $noRegionCode = ['ar', 'id', 'pa', 'sl', 'ca'];
    /**
     * A list of locales that will be activated, no matter if translation files exist for them.
     *
     * @var string[]
     */
    private array $addLocaleToList = ['zh_Hant_TW'];
    /**
     * A list of locales that will NOT be activated, as no translations exist by now.
     *
     * @var string[]
     */
    private array $skipLocale = ['ca'];

    public function __construct(
        private readonly string $projectDirectory,
        private readonly string $kernelEnvironment
    )
    {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->kernelEnvironment !== 'prod';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // find all available locales from the translation filenames
        $translationFilenames = glob($this->projectDirectory . DIRECTORY_SEPARATOR . 'translations/*.xlf');
        if ($translationFilenames === false) {
            $io->error('Failed reading translation files');

            return Command::FAILURE;
        }
        $firstLevelLocales = [];
        foreach ($translationFilenames as $file) {
            $l = explode('.', basename($file))[1];
            if (\in_array($l, $this->skipLocale, true)) {
                continue;
            }
            $firstLevelLocales[] = $l;
        }
        $firstLevelLocales = array_unique(array_merge($firstLevelLocales, $this->addLocaleToList));
        $io->title('First level locales found');
        $io->writeln(implode('|', $firstLevelLocales));

        $secondLevel = [];
        foreach (Locales::getLocales() as $localeCode) {
            $locale = explode('_', $localeCode);
            if (\count($locale) === 2 && !\in_array($locale[0], $this->noRegionCode, true)) {
                $baseLocale = $locale[0];
                if (\in_array($baseLocale, $firstLevelLocales)) {
                    $regionCode = $locale[1];
                    if (!is_numeric($regionCode)) {
                        $secondLevel[] = $localeCode;
                    }
                }
            }
        }

        sort($firstLevelLocales);
        sort($secondLevel);

        // keep the locales that have translation files at the beginning
        // the config is than easier to read and the locales will be sorted in the UI anyway
        $locales = array_merge($firstLevelLocales, $secondLevel);

        $appLocales = [];

        // make sure all allowed locales are registered
        foreach ($locales as $locale) {
            if (!Locales::exists($locale)) {
                continue;
            }

            $appLocales[$locale] = LocaleService::DEFAULT_SETTINGS;
        }

        // make sure all keys are registered for every locale
        foreach ($appLocales as $locale => $settings) {
            $settings['translation'] = \in_array($locale, $firstLevelLocales, true);

            // these are completely new since v2
            // calculate everything with IntlFormatter
            $shortDate = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
            $shortTime = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT);

            $settings['date'] = $shortDate->getPattern();
            if ($settings['date'] === false) {
                $io->error('Invalid date pattern for locale: ' . $locale);
                continue;
            }
            $settings['time'] = $shortTime->getPattern();
            if ($settings['time'] === false) {
                $io->error('Invalid time pattern for locale: ' . $locale);
                continue;
            }

            // see https://github.com/kimai/kimai/issues/4402 - Korean time format failed parsing
            // special case when time pattern starts with A / a => this will lead to an error
            // \DateTimeImmutable::getLastErrors() => Meridian can only come after an hour has been found
            if (str_contains($settings['time'], 'a ')) {
                $settings['time'] = str_replace('a ', '', $settings['time']) . ' a';
            }
            $settings['time'] = str_replace("\u{202f}", ' ', $settings['time']);

            // make sure that sub-locales of a RTL language are also flagged as RTL
            $rtlLocale = $locale;
            if (substr_count($rtlLocale, '_') === 1) {
                $rtlLocale = substr($rtlLocale, 0, strpos($rtlLocale, '_'));
            }

            $settings['rtl'] = \in_array($rtlLocale, $this->rtlLocales, true);

            // pre-fill all formats with the default locale settings
            $appLocales[$locale] = $settings;
        }

        $removableDuplicates = [];
        foreach ($appLocales as $locale => $setting) {
            $localeParts = explode('_', $locale);
            if (\count($localeParts) === 1) {
                continue;
            }
            // e.g. norwegian just exists with region code
            if (!\array_key_exists($localeParts[0], $appLocales)) {
                continue;
            }
            $baseLocaleSettings = $appLocales[$localeParts[0]];
            if ($baseLocaleSettings['time'] !== $setting['time']) {
                continue;
            }
            if ($baseLocaleSettings['date'] !== $setting['date']) {
                continue;
            }
            if ($setting['translation'] === true) {
                continue;
            }
            if ($baseLocaleSettings['rtl'] !== $setting['rtl']) {
                continue;
            }
            $removableDuplicates[] = $locale;
        }

        /*
        $io->title('Redundant locales that could be skipped');
        $io->writeln(implode('|', $removableDuplicates));

        foreach ($removableDuplicates as $duplicate) {
            unset($appLocales[$duplicate]);
        }
        */

        // in the future this list should be reduced to the list of available translations, but for a long time users
        // could choose from the entire list of all locales, so we likely have to keep that forever ...
        $io->title('List of "kimai_locales" for services.yaml');
        $io->writeln("['" . implode("', '", $locales) . "']");

        ksort($appLocales);

        $filename = 'config/locales.php';
        $targetFile = $this->projectDirectory . DIRECTORY_SEPARATOR . $filename;

        $content = '<?php return ' . var_export($appLocales, true) . ';';
        $content = str_replace('array (', '[', $content);
        $content = str_replace(')', ']', $content);

        file_put_contents($targetFile, $content);

        $io->success('Created new locale definition at: ' . $filename);

        return Command::SUCCESS;
    }
}
