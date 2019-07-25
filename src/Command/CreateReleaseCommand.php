<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to create a release package with pre-installed composer, SQLite database and user.
 *
 * @codeCoverageIgnore
 */
class CreateReleaseCommand extends Command
{
    public const CLONE_CMD = 'git clone -b %s --depth 1 https://github.com/kevinpapst/kimai2.git';

    /**
     * @var string
     */
    protected $rootDir = '';

    /**
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->rootDir = realpath($projectDirectory);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:create-release')
            ->setDescription('Create a pre-installed release package')
            ->setHelp('This command will create a release package with pre-installed composer, SQLite database and user.')
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'Directory where the release package will be stored', 'var/data/')
            ->addOption('release', null, InputOption::VALUE_OPTIONAL, 'The version that should be zipped', Constants::VERSION)
        ;

        /**
         * Hide this command in production.
         * Maybe it should be de-activated completely?!
         */
        if (getenv('APP_ENV') === 'prod') {
            $this->setHidden(true);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (getenv('APP_ENV') === 'prod') {
            $io->error('kimai:create-release is not allowed in production');

            return -2;
        }

        $directory = $input->getOption('directory');

        if ($directory[0] === '/') {
            $directory = realpath($directory);
        } else {
            $directory = realpath($this->rootDir . '/' . $directory);
        }

        $tmpDir = $directory . '/' . uniqid('kimai_release_');

        if (!is_dir($directory)) {
            $io->error('Given directory is not existing: ' . $directory);

            return 1;
        }

        if (is_dir($directory) && !is_writable($directory)) {
            $io->error('Cannot write in directory: ' . $directory);

            return 1;
        }

        $version = $input->getOption('release');

        $io->success('Prepare new packages for Kimai ' . $version . ' in ' . $tmpDir);

        $gitCmd = sprintf(self::CLONE_CMD, $version);
        $tar = 'kimai-release-' . $version;
        $zip = 'kimai-release-' . $version;

        if ($version === Constants::VERSION && Constants::STATUS !== 'stable') {
            $tar .= '_' . Constants::STATUS;
            $zip .= '_' . Constants::STATUS;
        }

        $tar .= '.tar.gz';
        $zip .= '.zip';

        // this removes the current env settings, as they might differ from the release ones
        // if we don't unset them, the .env file won't be read when executing bin/console commands
        putenv('DATABASE_URL');
        putenv('APP_ENV');

        $commands = [
            'Clone repository' => $gitCmd . ' ' . $tmpDir,
            'Install composer dependencies' => 'cd ' . $tmpDir . ' && composer install --no-dev --optimize-autoloader',
            'Create database' => 'cd ' . $tmpDir . ' && bin/console doctrine:database:create -n',
            'Create tables' => 'cd ' . $tmpDir . ' && bin/console doctrine:schema:create -n',
            'Add all migrations' => 'cd ' . $tmpDir . ' && bin/console doctrine:migrations:version --add --all -n',
        ];

        $filesToDelete = [
            '.git*',
            '.codecov.yml',
            '.editorconfig',
            '.php_cs.dist',
            '.travis.yml',
            '*.lock',
            'package.json',
            'phpstan.neon',
            'Dockerfile',
            'phpunit.xml.dist',
            'webpack.config.js',
            'assets/',
            'bin/',
            'tests/',
            'var/cache/*',
            'var/data/kimai_test.sqlite',
            'var/log/*.log',
            'var/sessions/*',
        ];

        foreach ($filesToDelete as $deleteMe) {
            $commands['Delete ' . $deleteMe] = 'cd ' . $tmpDir . ' && rm -rf ' . $deleteMe;
        }

        $commands = array_merge($commands, [
            'Create tar' => 'cd ' . $tmpDir . ' && tar -czf ' . $directory . '/' . $tar . ' .',
            'Create zip' => 'cd ' . $tmpDir . ' && zip -r ' . $directory . '/' . $zip . ' .',
            'Remove tmp directory' => 'rm -rf ' . $tmpDir,
        ]);

        $exitCode = 0;
        foreach ($commands as $title => $command) {
            $io->success($title);
            passthru($command, $exitCode);
            if ($exitCode !== 0) {
                $io->error('Failed with command: ' . $command);

                return -1;
            }
        }

        $io->success(
            'New release packages available at: ' . PHP_EOL .
            $directory . '/' . $tar . PHP_EOL .
            $directory . '/' . $zip
        );

        return 0;
    }
}
