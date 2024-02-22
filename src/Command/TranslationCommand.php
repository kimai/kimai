<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\LocaleService;
use App\Kernel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:translations')]
final class TranslationCommand extends Command
{
    public function __construct(private string $projectDirectory, private string $kernelEnvironment, private LocaleService $localeService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Translation adjustments')
            ->addOption('resname', null, InputOption::VALUE_NONE, 'Fix the resname vs. id attribute')
            ->addOption('duplicates', null, InputOption::VALUE_NONE, 'Find duplicate translation keys')
            ->addOption('delete-resname', null, InputOption::VALUE_REQUIRED, 'Deletes the translation by resname')
            ->addOption('extension', null, InputOption::VALUE_NONE, 'Find translation files with wrong extensions')
            ->addOption('fill-empty', null, InputOption::VALUE_NONE, 'Pre-fills empty translations with the english version')
            ->addOption('delete-empty', null, InputOption::VALUE_NONE, 'Delete all empty keys and files which have no translated key at all')
            ->addOption('move-resname', null, InputOption::VALUE_REQUIRED, 'Move a resname from one file to another (needs "source" and "target" options)')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Single source file to use')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Single target file to use')
            // DEEPL TRANSLATION FEATURE - UNTESTED
            ->addOption('translate-locale', null, InputOption::VALUE_REQUIRED, 'Translate into the given locale with Deepl')
            // @see https://www.deepl.com/de/pro#developer
            ->addOption('translate-deepl', null, InputOption::VALUE_REQUIRED, 'Translate using the "DeepL API Free" auth-key')
        ;
    }

    public function isEnabled(): bool
    {
        return $this->kernelEnvironment !== 'prod';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bases = [
            'core' => $this->projectDirectory . '/translations/*.xlf',
            'plugins' => $this->projectDirectory . Kernel::PLUGIN_DIRECTORY . '/*/Resources/translations/*.xlf',
            'theme' => $this->projectDirectory . '/vendor/kevinpapst/tabler-bundle/translations/*.xlf',
        ];

        $sources = [];
        if ($input->getOption('source') !== null) {
            $tmp = $input->getOption('source');
            foreach ($bases as $directory) {
                $files = glob($directory);
                foreach ($files as $file) {
                    if (explode('.', basename($file))[0] === $tmp) {
                        $sources[] = $file;
                    }
                }
            }
            if (\count($sources) === 0) {
                $io->error('Could not find translation "source" named: ' . $tmp);

                return Command::FAILURE;
            }
        } else {
            foreach ($bases as $directory) {
                $files = glob($directory);
                foreach ($files as $file) {
                    $sources[] = $file;
                }
            }
        }

        $targets = [];
        if ($input->getOption('target') !== null) {
            $tmp = $input->getOption('target');
            foreach ($bases as $directory) {
                $files = glob($directory);
                foreach ($files as $file) {
                    if (explode('.', basename($file))[0] === $tmp) {
                        $targets[] = $file;
                    }
                }
            }
            if (\count($targets) === 0) {
                $io->error('Could not find translation "target" named: ' . $tmp);

                return Command::FAILURE;
            }
        }

        if ($input->getOption('delete-resname')) {
            foreach ($sources as $file) {
                $this->removeKey($file, $input->getOption('delete-resname'));
            }
        }

        // ==========================================================================
        // Fix resname vs. id
        // ==========================================================================
        if ($input->getOption('resname')) {
            foreach ($sources as $file) {
                $this->fixXlfFile($file);
            }
        }

        // ==========================================================================
        // Move resname from source to target
        // ==========================================================================
        $moveResname = $input->getOption('move-resname');
        if (\is_string($moveResname)) {
            if (\count($targets) === 0) {
                $io->error('Moving a resname needs a target file');

                return Command::FAILURE;
            }

            return $this->moveResname($io, $moveResname, $sources, $targets);
        }

        // ==========================================================================
        // Fill empty translations with english version
        // ==========================================================================
        if ($input->getOption('fill-empty')) {
            $translateFrom = ['de-CH' => 'de', 'de_CH' => 'de', 'pt_BR' => 'pt', 'pt-BR' => 'pt', 'pt' => 'pt_BR'];
            $translations = [];
            foreach ($sources as $file) {
                $base = basename($file);
                $parts = explode('.', $base);
                $name = $parts[0];
                $fileLocale = $parts[1];
                $fromLocale = 'en';
                if (\array_key_exists($fileLocale, $translateFrom)) {
                    $fromLocale = $translateFrom[$fileLocale];
                }

                if (!\array_key_exists($fromLocale, $translations)) {
                    $translations[$fromLocale] = [];
                }

                if (!\array_key_exists($name, $translations[$fromLocale])) {
                    $fromLocaleName = str_replace('.' . $fileLocale . '.', '.' . $fromLocale . '.', $file);
                    if (!file_exists($fromLocaleName)) {
                        $io->error('Could not find translation file: ' . $fromLocaleName);

                        return Command::FAILURE;
                    }
                    $translations[$fromLocale][$name] = $this->getTranslations($fromLocaleName);
                }

                if (stripos($base, '.' . $fromLocale . '.xlf') !== false || stripos($base, '.' . $fromLocale . '.xliff') !== false) {
                    continue;
                }

                $this->fillEmptyTranslations($file, $translations[$fromLocale][$name]);
            }
        }

        // ==========================================================================
        // Find wrong file extensions
        // ==========================================================================
        if ($input->getOption('extension')) {
            foreach ($sources as $file) {
                $file = str_replace($this->projectDirectory, '', $file);
                $io->warning($file);
            }
        }

        // ==========================================================================
        // Find duplicate translation keys
        // ==========================================================================
        if ($input->getOption('duplicates')) {
            $duplicates = [];

            foreach ($sources as $file) {
                $xml = simplexml_load_file($file);
                foreach ($xml->file->body->{'trans-unit'} as $unit) {
                    $n = (string) $unit['resname'];
                    if (!\array_key_exists($n, $duplicates)) {
                        $duplicates[$n] = [];
                    }
                    $b = explode('.', basename($file))[0];
                    if (!\in_array($b, $duplicates[$n])) {
                        $duplicates[$n][] = $b;
                    }
                }
            }

            foreach ($duplicates as $id => $duplicateFiles) {
                if (\count($duplicateFiles) > 1) {
                    $io->text($id . ' => ' . implode(', ', $duplicateFiles));
                }
            }
        }

        // ==========================================================================
        // Delete empty translation keys and files without any translation
        // ==========================================================================
        if ($input->getOption('delete-empty')) {
            foreach ($sources as $file) {
                $this->removeEmptyTranslations($io, $file);
            }
        }

        // ==========================================================================
        // DEEPL
        // ==========================================================================
        $locale = $input->getOption('translate-locale');
        $deepl = $input->getOption('translate-deepl');

        if ($locale !== null && $deepl === null) {
            $io->error('Missing "DeepL API Free" auth-key');

            return Command::FAILURE;
        }

        if ($locale === null && $deepl !== null) {
            $io->error('Missing translation locale');

            return Command::FAILURE;
        }

        if ($locale !== null && $deepl !== null) {
            // see https://github.com/octfx/DeepLy/blob/master/src/DeepLy.php
            $deeplySupportedLanguages = [
                'de' => 'DE',
                'en' => 'EN-US',
                'fr' => 'FR',
                'it' => 'IT',
                'ja' => 'JA',
                'es' => 'ES',
                'nl' => 'NL',
                'pl' => 'PL',
                'pt' => 'PT-PT', // ???
                'pt_BR' => 'PT-BR', // ???
                'ru' => 'RU',
                'zh_CN' => 'ZH',
            ];

            $locale = strtolower($locale);
            if (!$this->localeService->isKnownLocale($locale)) {
                $io->error('Unknown locale given: ' . $locale);

                return Command::FAILURE;
            }

            if (!\array_key_exists($locale, $deeplySupportedLanguages)) {
                $io->error('Locale not supported by Deeply: ' . $locale);

                return Command::FAILURE;
            }

            $allKeys = 0;
            $enFiles = glob($bases['core'] . '/*.en.xlf');

            $baseUrl = 'https://api-free.deepl.com/v2/translate';
            $client = HttpClient::create([]);

            foreach ($enFiles as $file) {
                $enTrans = [];
                $domain = explode('.', basename($file))[0];

                $xml = simplexml_load_file($file);

                foreach ($xml->file->body->{'trans-unit'} as $unit) {
                    $id = (string) $unit['id'];
                    $enTrans[$id] = [
                        'resname' => (string) $unit['resname'],
                        'source' => (string) $unit->source,
                        'target' => (string) $unit->target
                    ];
                    $allKeys++;
                }

                $localeFile = $bases['core'] . '/' . $domain . '.' . $locale . '.xlf';

                $translated = [];

                if (file_exists($localeFile)) {
                    $xml2 = simplexml_load_file($localeFile);
                    foreach ($xml2->file->body->{'trans-unit'} as $unit) {
                        $id = (string) $unit['id'];
                        $translated[$id] = [
                            'resname' => (string) $unit['resname'],
                            'source' => (string) $unit->source,
                            'target' => (string) $unit->target
                        ];
                    }
                }

                $missingIds = array_diff(array_keys($enTrans), array_keys($translated));
                if (\count($missingIds) === 0) {
                    continue;
                }

                $io->title('Translating ' . $domain);
                $progress = new ProgressBar($output, \count($missingIds));

                foreach ($missingIds as $id) {
                    $progress->advance();

                    $values = $enTrans[$id];
                    $translated[$id] = $values;

                    $params = [
                        'auth_key' => $deepl,
                        //'split_sentences' => '1',
                        //'preserve_formatting' => '0',
                        'formality' => 'default',
                        'text' => $values['target'],
                        'source_lang' => 'en',
                        'target_lang' => $deeplySupportedLanguages[$locale],
                    ];

                    $rawResponseData = null;
                    try {
                        $rawResponseData = $client->request('POST', $baseUrl, ['body' => $params]);
                    } catch (\Exception $exception) {
                        $io->error($exception->getMessage());

                        return Command::FAILURE;
                    }

                    $json = json_decode($rawResponseData->getContent(), true);
                    $translation = $json['translations'][0]['text'];

                    $translated[$id]['target'] = $translation;
                }

                $progress->finish();
                $io->writeln(PHP_EOL);

                $this->writeXliffFile($bases['core'], $domain, $locale, $translated);
            }
        }

        return Command::SUCCESS;
    }

    private function getTranslations(string $file): array
    {
        $translations = [];

        $xml = simplexml_load_file($file);
        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            if (!isset($unit['resname'])) {
                throw new \Exception('Missing "resname" attribute in file: ' . $file);
            }

            $source = (string) $unit['resname'];
            $translations[$source] = (string) $unit->target;
        }

        return $translations;
    }

    private function writeXliffFile(string $base, string $domain, string $locale, array $translations = []): void
    {
        $from = $base . '/' . $domain . '.en.xlf';
        $to = $base . '/' . $domain . '.' . $locale . '.xlf';

        copy($from, $to);

        $xml = simplexml_load_file($to);

        /** @var \SimpleXMLElement $fileNode */
        $fileNode = $xml->file;
        $fileNode->attributes()->{'target-language'} = $locale;
        $fileNode->attributes()->{'original'} = $domain . '.en.xlf';

        unset($xml->file->body);

        $xmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($xml->asXML());

        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('ns', $xmlDocument->documentElement->namespaceURI);

        $xmlContent = '';
        foreach ($translations as $id => $values) {
            $xmlContent .= sprintf(
                '<trans-unit id="%s" resname="%s"><source>%s</source><target>%s</target></trans-unit>',
                $id,
                $values['resname'],
                $values['source'],
                $values['target']
            );
        }

        $fragment = $xmlDocument->createDocumentFragment();
        $fragment->appendXML('<body>' . $xmlContent . '</body>');

        /** @var \DOMElement $element */
        $element = $xpath->evaluate('/ns:xliff/ns:file')->item(0);
        $element->appendChild($fragment);

        file_put_contents($to, $xmlDocument->saveXML());
    }

    private function fixXlfFile(string $file): void
    {
        $xml = simplexml_load_file($file);
        if (isset($xml->file->header)) {
            unset($xml->file->header);
        }

        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            $source = $unit->source;
            if (!isset($unit['resname'])) {
                $unit['resname'] = $source;
            }
            $unit['id'] = $this->generateId($unit['resname']);
        }

        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($xml->asXML());

        file_put_contents($file, $xmlDocument->saveXML());
    }

    private function fillEmptyTranslations(string $file, array $translations): void
    {
        $xml = simplexml_load_file($file);
        $foundEmpty = false;

        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            if (!isset($unit['resname'])) {
                continue;
            }

            $key = (string) $unit['resname'];

            $translation = (string) $unit->target;
            if (\strlen($translation) > 0) {
                continue;
            }

            if (!\array_key_exists($key, $translations)) {
                throw new \Exception(
                    sprintf('Missing english translation for key: %s in file %s', $key, $file)
                );
            }
            $unit->target[0] = $translations[$key];
            $unit->target['state'] = 'needs-translation';
            $foundEmpty = true;
        }

        if (!$foundEmpty) {
            return;
        }

        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($xml->asXML());

        file_put_contents($file, $xmlDocument->saveXML());
    }

    private function removeKey(string $file, string $key): void
    {
        $xml = simplexml_load_file($file);

        /** @var \SimpleXMLElement $unit */
        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            if (!isset($unit['resname'])) {
                continue;
            }

            if ((string) $unit['resname'] === $key) {
                $dom = dom_import_simplexml($unit);
                $dom->parentNode->removeChild($dom);
                break;
            }
        }

        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($xml->asXML());

        file_put_contents($file, $xmlDocument->saveXML());
    }

    private function removeEmptyTranslations(SymfonyStyle $io, string $file): void
    {
        $xml = simplexml_load_file($file);
        $hasTranslation = false;

        /** @var \SimpleXMLElement $unit */
        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            $translation = (string) $unit->target;
            if (\strlen($translation) > 0) {
                $hasTranslation = true;
                break;
            }
        }

        if (!$hasTranslation) {
            unlink($file);
            $io->warning('Removed empty translation file: ' . basename($file));

            return;
        }

        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->loadXML($xml->asXML());

        $removedTranslation = false;
        $elements = $xmlDocument->getElementsByTagName('target');
        foreach ($elements as $element) {
            if ($element->nodeValue === '' || $element->nodeValue === null) {
                /** @var \DOMElement $parent */
                $parent = $element->parentNode;
                $io->text('Remove empty translation: ' . $parent->getAttribute('resname'));
                $parent->parentNode->removeChild($parent);
                $removedTranslation = true;
            }
        }

        if ($removedTranslation) {
            $xmlDocument->formatOutput = true;

            file_put_contents($file, $xmlDocument->saveXML());
            $io->warning('Removed empty translations from file: ' . basename($file));
        }
    }

    private function generateId(string $source): string
    {
        return strtr(substr(base64_encode(hash('sha256', $source, true)), 0, 7), '/+', '._');
    }

    /**
     * @param array<string> $sources
     * @param array<string> $targets
     */
    private function moveResname(SymfonyStyle $io, string $resname, array $sources, array $targets): int
    {
        foreach ($sources as $source) {
            $tmp = basename($source);
            $pos = strpos($tmp, '.');
            if ($pos === false) {
                $io->error('Unexpected filename: ' . $source);

                return Command::FAILURE;
            }
            $suffix = substr($tmp, $pos);
            $target = null;
            foreach ($targets as $t) {
                if (str_ends_with($t, $suffix)) {
                    $target = $t;
                }
            }

            if ($target === null) {
                $io->error('Cannot find translation target file for source: ' . $source);

                return Command::FAILURE;
            }

            $sourceDocument = new \DOMDocument('1.0');
            $sourceDocument->load($source);

            $targetDocument = new \DOMDocument('1.0');
            $targetDocument->load($target);

            $movedNode = false;

            /** @var \DOMElement $element */
            foreach ($sourceDocument->getElementsByTagName('trans-unit') as $element) {
                if (!$element->hasAttribute('resname')) {
                    continue;
                }

                $key = $element->getAttribute('resname');

                if ($key === $resname) {
                    $newNode = $targetDocument->importNode($element, true);
                    $targetDocument->documentElement->firstElementChild->firstElementChild->appendChild($newNode); // @phpstan-ignore-line
                    $element->parentNode->removeChild($element); // @phpstan-ignore-line
                    $movedNode = true;
                    break;
                }
            }

            if ($movedNode) {
                $xmlDocument = new \DOMDocument('1.0');
                $xmlDocument->preserveWhiteSpace = false;
                $xmlDocument->formatOutput = true;
                $xmlDocument->loadXML($sourceDocument->saveXML()); // @phpstan-ignore-line
                file_put_contents($source, $xmlDocument->saveXML());

                $xmlDocument = new \DOMDocument('1.0');
                $xmlDocument->preserveWhiteSpace = false;
                $xmlDocument->formatOutput = true;
                $xmlDocument->loadXML($targetDocument->saveXML()); // @phpstan-ignore-line
                file_put_contents($target, $xmlDocument->saveXML());
            }
        }

        return Command::SUCCESS;
    }
}
