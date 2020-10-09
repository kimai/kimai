<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\FormConfiguration;
use App\Entity\Customer;
use App\Entity\Team;
use App\Importer\CsvReader;
use App\Importer\DefaultProjectImporter;
use App\Importer\ImportNotFoundException;
use App\Importer\ImportNotReadableException;
use App\Importer\ImportReader;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command can change anytime, don't rely on its API for the future!
 *
 * @internal
 * @codeCoverageIgnore
 */
class ImportProjectCommand extends Command
{
    /**
     * @var CustomerRepository
     */
    private $customers;
    /**
     * @var ProjectRepository
     */
    private $projects;
    /**
     * @var TeamRepository
     */
    private $teams;
    /**
     * @var UserRepository
     */
    private $users;
    /**
     * @var FormConfiguration
     */
    private $configuration;
    /**
     * @var Customer[]
     */
    private $customerCache = [];
    /**
     * The datetime of this import as formatted string.
     *
     * @var string
     */
    private $dateTime = '';
    /**
     * Comment that will be added to new customers, projects and activities.
     *
     * @var string
     */
    private $comment = '';

    public function __construct(CustomerRepository $customers, ProjectRepository $projects, TeamRepository $teams, UserRepository $users, FormConfiguration $configuration)
    {
        parent::__construct();
        $this->customers = $customers;
        $this->projects = $projects;
        $this->teams = $teams;
        $this->users = $users;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:import:project')
            ->setDescription('Import projects from CSV file')
            ->setHelp(
                'Import projects from a CSV file, creating customers (if not existing) and optional empty teams for each project.' . PHP_EOL .
                'Imported customer will be matched by name and optionally created on the fly.' . PHP_EOL
            )
            ->addArgument('file', InputArgument::REQUIRED, 'The CSV file to be imported')
            ->addOption('importer', null, InputOption::VALUE_REQUIRED, 'The importer to use (supported: default)', 'default')
            ->addOption('reader', null, InputOption::VALUE_REQUIRED, 'The reader to use (supported: csv, csv-semicolon)', 'csv')
            ->addOption('teamlead', null, InputOption::VALUE_REQUIRED, 'If you want to create empty teams for each project, give the username of the teamlead to be assigned')
        ;
    }

    protected function getImporter(?string $importer = null)
    {
        switch ($importer) {
            case 'default':
                return new DefaultProjectImporter($this->projects, $this->customers, $this->configuration);
        }

        throw new \Exception('Unknown importer');
    }

    protected function getReader(?string $reader = null): ImportReader
    {
        switch ($reader) {
            case 'csv':
                return new CsvReader(',');
            case 'csv-semicolon':
                return new CsvReader(';');
        }

        throw new \Exception('Unknown reader');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Kimai importer: Projects');

        // validate teamlead
        $teamlead = $input->getOption('teamlead');
        if (null !== $teamlead) {
            $tmpUser = $this->users->findBy(['username' => $teamlead]);
            if (empty($tmpUser) || \count($tmpUser) > 1) {
                $tmpUser = $this->users->findBy(['email' => $teamlead]);
                if (empty($tmpUser) || \count($tmpUser) > 1) {
                    $io->error(
                        sprintf(
                            'You requested to create empty teams for each project, but the given teamlead cannot be found.' . PHP_EOL .
                            'Please create a user with the name (or email) %s first, before continuing.' . PHP_EOL,
                            $teamlead
                        )
                    );

                    return 3;
                }
            }

            $teamlead = $tmpUser[0];
        }

        $doImport = true;
        $row = 1;
        $errors = 0;
        $projects = [];

        $importer = $this->getImporter($input->getOption('importer'));
        $reader = $this->getReader($input->getOption('reader'));
        $importerFile = $input->getArgument('file');

        $io->text('Reading import file ...');

        try {
            $records = $reader->read($importerFile);
        } catch (ImportNotFoundException $ex) {
            $io->error('File not existing: ' . $importerFile);

            return 1;
        } catch (ImportNotReadableException $ex) {
            $io->error('File cannot be read: ' . $importerFile);

            return 2;
        }

        $amount = iterator_count($records);
        $records->rewind();
        $io->text(sprintf('Found %s rows to process, converting now...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        foreach ($records as $record) {
            try {
                $projects[] = $importer->convertEntryToProject($record);
            } catch (\Exception $ex) {
                $io->error(sprintf('Invalid row %s: %s', $row, $ex->getMessage()));
                $doImport = false;
                $errors++;
            }
            $progressBar->advance();

            $row++;
        }
        $progressBar->finish();
        $io->writeln('');

        if (!$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 4;
        }

        $createdProjects = 0;
        $updatedProjects = 0;
        $createdCustomers = 0;

        $amount = \count($projects);
        $io->text(sprintf('Converted %s projects, importing into Kimai now...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        foreach ($projects as $project) {
            $row++;
            $progressBar->advance();
            try {
                if ($project->getCustomer()->getId() === null) {
                    $this->customers->saveCustomer($project->getCustomer());
                    $createdCustomers++;
                }

                if ($project->getId() === null) {
                    $createdProjects++;
                } else {
                    $updatedProjects++;
                }
                $this->projects->saveProject($project);

                if (null === $teamlead) {
                    continue;
                }

                $team = new Team();
                $team->setName($project->getName());
                $team->setTeamLead($teamlead);

                $this->teams->saveTeam($team);

                $project->addTeam($team);
                $team->addProject($project);

                $this->teams->saveTeam($team);
                $this->projects->saveProject($project);
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing project row %s with: %s', $row, $ex->getMessage()));

                return 5;
            }
        }
        $progressBar->finish();
        $io->writeln('');

        if ($createdCustomers === 0 && $updatedProjects === 0 && $createdProjects === 0) {
            $io->text('Nothing was imported');
        } else {
            if ($createdCustomers > 0) {
                $io->success(sprintf('Imported %s customers', $createdCustomers));
            }
            if ($updatedProjects > 0) {
                $io->success(sprintf('Updated %s projects', $updatedProjects));
            }
            if ($createdProjects > 0) {
                $io->success(sprintf('Imported %s projects', $createdProjects));
            }
        }

        return 0;
    }
}
