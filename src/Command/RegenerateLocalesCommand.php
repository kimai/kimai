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
#[AsCommand(name: 'kimai:reset:locales')]
final class RegenerateLocalesCommand extends Command
{
    private string $defaultDate = 'dd.MM.y';
    private string $defaultTime = 'HH:mm';
    private array $rtlLocales = [
        'ar' => true,
        'fa' => true,
        'he' => true,
    ];

    public function __construct(private LocaleService $localeService, private string $projectDirectory, private string $kernelEnvironment)
    {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->kernelEnvironment !== 'prod';
    }

    protected function configure(): void
    {
        $this->setDescription('Regenerate the locale definition file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locales = $this->localeService->getAllLocales();

        // detect all registered locales and allow to choose them as well, so people get to
        // choose the language for translation with the correct format of their location
        /*
        $secondLevel = [];
        foreach (Locales::getLocales() as $locale) {
            if (substr_count($locale, '_') === 1) {
                $baseLocale = substr($locale, 0, strpos($locale, '_'));
                if (in_array($baseLocale, $locales)) {
                    $subLocale = substr($locale, strpos($locale, '_') + 1);
                    if (!is_numeric($subLocale)) {
                        $secondLevel[] = $locale;
                    }
                }
            }
        }
        $locales = array_merge($locales, $secondLevel);
        */

        $appLocales = [];
        $defaults = [
            'date' => $this->defaultDate,
            'time' => $this->defaultTime,
            'rtl' => false,
        ];

        // make sure all allowed locales are registered
        foreach ($locales as $locale) {
            if (!Locales::exists($locale)) {
                continue;
            }

            $appLocales[$locale] = $defaults;
        }

        // make sure all keys are registered for every locale
        foreach ($appLocales as $locale => $settings) {
            // these are completely new since v2
            // calculate everything with IntlFormatter
            $shortDate = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
            $shortTime = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT);

            $settings['date'] = $shortDate->getPattern();
            $settings['time'] = $shortTime->getPattern();

            // make sure that sub-locales of a RTL language are also flagged as RTL
            $rtlLocale = $locale;
            if (substr_count($rtlLocale, '_') === 1) {
                $rtlLocale = substr($rtlLocale, 0, strpos($rtlLocale, '_'));
            }

            if (\array_key_exists($rtlLocale, $this->rtlLocales)) {
                $settings['rtl'] = $this->rtlLocales[$rtlLocale];
            }

            // pre-fill all formats with the default locale settings
            $appLocales[$locale] = $settings;
        }

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
