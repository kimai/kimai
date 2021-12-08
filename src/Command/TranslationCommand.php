<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * @codeCoverageIgnore
 */
class TranslationCommand extends Command
{
    private $projectDirectory;
    private $environment;

    public function __construct(string $projectDirectory, string $kernelEnvironment)
    {
        parent::__construct();
        $this->projectDirectory = $projectDirectory;
        $this->environment = $kernelEnvironment;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:translations')
            ->setDescription('Translation adjustments')
            ->addOption('resname', null, InputOption::VALUE_NONE, 'Fix the resname vs. id attribute')
            ->addOption('duplicates', null, InputOption::VALUE_NONE, 'Find duplicate translation keys')
            ->addOption('delete-resname', null, InputOption::VALUE_REQUIRED, 'Deletes the translation by resname')
            ->addOption('extension', null, InputOption::VALUE_NONE, 'Find translation files with wrong extensions')
        ;
    }

    public function isEnabled(): bool
    {
        return $this->environment !== 'prod';
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $bases = [
            'core' => $this->projectDirectory . '/translations',
            'plugins' => $this->projectDirectory . Kernel::PLUGIN_DIRECTORY . '/*/Resources/translations',
        ];

        if ($input->getOption('delete-resname')) {
            $files = glob($bases['core'] . '/*.xlf');
            foreach ($files as $file) {
                $this->removeKey($file, $input->getOption('delete-resname'));
            }
        }

        if ($input->getOption('resname')) {
            foreach ($bases as $id => $directory) {
                $files = glob($directory . '/*.xlf');

                foreach ($files as $file) {
                    $this->fixXlfFile($file);
                }
            }
        }

        if ($input->getOption('extension')) {
            foreach ($bases as $id => $directory) {
                $files = glob($directory . '/*.xliff');

                foreach ($files as $file) {
                    $file = str_replace($this->projectDirectory, '', $file);
                    $io->warning($file);
                }
            }
        }

        if ($input->getOption('duplicates')) {
            $duplicates = [];

            $files = glob($bases['core'] . '/*.xlf');
            foreach ($files as $file) {
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

            foreach ($duplicates as $id => $files) {
                if (\count($files) > 1) {
                    $io->text($id . ' => ' . implode(', ', $files));
                }
            }
        }

        return 0;
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
            $unit['id'] = strtr(substr(base64_encode(hash('sha256', $source, true)), 0, 7), '/+', '._');
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
}
