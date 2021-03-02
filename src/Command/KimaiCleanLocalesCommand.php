<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Yaml\Yaml;

class KimaiCleanLocalesCommand extends Command
{
    /**
     * [protected description]
     * @var [type]
     */
    protected static $defaultName = 'kimai:clean-locales';

    /**
     * [private description]
     * @var [type]
     */
    private $kernel;
    /**
     * @var string
     */
    private $environment;

    /**
     * [__construct description]
     * @method __construct
     * @param KernelInterface $kernel [description]
     */
    public function __construct(KernelInterface $kernel, string $kernelEnvironment)
    {
        parent::__construct();
        $this->kernel = $kernel;
        $this->environment = $kernelEnvironment;
    }

    /**
     * Make sure that this command CANNOT be executed in production.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->environment !== 'prod';
    }

    /**
     * [configure description]
     * @method configure
     * @return [type] [description]
     */
    protected function configure()
    {
        $this
            ->setDescription('Locales cleaner')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filesystem = new Filesystem();
        $tmp = $this->kernel->getCacheDir() . '/tmp/lng_' . random_int(0, 1000000);

        try {
            $filesystem->mkdir($tmp, 0777);
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating your directory at ' . $exception->getPath();
        }
        /*
         * mirror folder
         */
        $filesystem->mirror('translations', $tmp);

        /*
         * get locale codes
         */
        $locale_codes = [];
        foreach (glob('translations/*.yaml') as $filename) {
            preg_match("#.*\.(\w+)\..*#Uis", $filename, $_loc);
            $locale = $_loc[1];

            if ($locale != 'en') {
                $locale_codes[] = $locale;
            }

            if (Locales::exists($locale)) {
                $commands['Create locale: ' . $locale] = 'php bin/console translation:update --force --output-format=yaml ' . $locale . ' --sort=asc';
            }
        }

        /*
         * clear cache
         */
        $commands = [
             'Clearing the cache' => 'php bin/console cache:clear'
        ];
        $exitCode = 0;
        /*
         * run commands
         */
        foreach ($commands as $title => $command) {
            passthru($command, $exitCode);
            if ($exitCode !== 0) {
                $io->error('Failed with command: ' . $command);

                return -1;
            } else {
                $io->success($title);
            }
        }
        /*
         * get locale names
         */
        $locale_names = [];
        foreach (glob('translations/*.en.yaml') as $filename) {
            preg_match("#translations/(.*)\.(\w+)\..*#Uis", $filename, $_loc);
            $locale_name = $_loc[1];
            $en_locale_array = Yaml::parseFile($filename);

            ksort($en_locale_array);
            $locale_names[] = $locale_name;

            file_put_contents($filename, Yaml::dump($en_locale_array));

            /*
             * merge en to all
             */
            foreach ($locale_codes as $locale_code) {
                $lang = 'translations/' . $locale_name . '.' . $locale_code . '.yaml';
                if (!$filesystem->exists($lang)) {
                    $lang = 'translations/' . $locale_name . '+intl-icu.' . $locale_code . '.yaml';
                    if (!$filesystem->exists($lang)) {
                        $filesystem->touch($lang);
                    }
                }
                $lang_locale_array = Yaml::parseFile($lang);
                $lang_sorted = ($lang_locale_array !== null) ?
                    $lang_sorted = array_replace($en_locale_array, $lang_locale_array) :
                    $en_locale_array;
                file_put_contents($lang, Yaml::dump($lang_sorted));
            }

            $io->success('Merge ' . $filename);
        }

        $io->success('You have a new locales! You can open them in any editor and edit like normal text.');

        return 0;
    }
}
