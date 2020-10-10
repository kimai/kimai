<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Team;
use App\Importer\ImporterService;
use App\Importer\ImportNotFoundException;
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
    private $importerService;
    private $teams;
    private $users;

    public function __construct(ImporterService $importerService, TeamRepository $teams, UserRepository $users)
    {
        parent::__construct();
        $this->importerService = $importerService;
        $this->teams = $teams;
        $this->users = $users;
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
            ->addOption('no-update', null, InputOption::VALUE_NONE, 'If you want to create new project, but not update existing ones')
        ;
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

        $skipUpdate = $input->getOption('no-update');
        $doImport = true;
        $row = 1;
        $errors = 0;
        $projects = [];

        $importer = $this->importerService->getProjectImporter($input->getOption('importer'));
        $reader = $this->importerService->getReader($input->getOption('reader'));
        $importerFile = $input->getArgument('file');

        $io->text('Reading import file ...');

        try {
            $records = $reader->read($importerFile);
        } catch (ImportNotFoundException $ex) {
            $io->error('File not existing or not readable: ' . $importerFile);

            return 1;
        }

        $amount = iterator_count($records);
        $records->rewind();
        $io->text(sprintf('Found %s rows to process, converting now ...', $amount));

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

            return 2;
        }

        $createdProjects = 0;
        $updatedProjects = 0;
        $noUpdatedProjects = 0;
        $createdCustomers = 0;

        $amount = \count($projects);
        $io->text(sprintf('Converted %s projects, importing into Kimai now ...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        foreach ($projects as $project) {
            $row++;
            $progressBar->advance();
            try {
                if ($project->getCustomer()->getId() === null) {
                    $this->importerService->importCustomer($project->getCustomer());
                    $createdCustomers++;
                }

                $createTeam = false;

                if ($project->getId() === null) {
                    $this->importerService->importProject($project);
                    $createdProjects++;
                    $createTeam = (null !== $teamlead);
                } elseif ($skipUpdate === false) {
                    $this->importerService->importProject($project);
                    $updatedProjects++;
                } else {
                    $noUpdatedProjects++;
                }

                if (!$createTeam) {
                    continue;
                }

                $team = new Team();
                $team->setName($project->getName());
                $team->setTeamLead($teamlead);

                $this->teams->saveTeam($team);

                $project->addTeam($team);
                $team->addProject($project);

                $this->teams->saveTeam($team);
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing project row %s with: %s', $row, $ex->getMessage()));

                return 3;
            }
        }

        $progressBar->finish();
        $io->writeln('');
        $io->writeln('');

        if ($createdCustomers === 0 && $updatedProjects === 0 && $createdProjects === 0) {
            if ($noUpdatedProjects > 0) {
                $io->success(sprintf('Skipped %s existing projects', $noUpdatedProjects));
            } else {
                $io->text('Nothing was imported');
            }
        } else {
            if ($createdCustomers > 0) {
                $io->success(sprintf('Imported %s customers', $createdCustomers));
            }
            if ($updatedProjects > 0) {
                $io->success(sprintf('Updated %s projects', $updatedProjects));
            }
            if ($noUpdatedProjects > 0) {
                $io->success(sprintf('Skipped %s existing projects', $noUpdatedProjects));
            }
            if ($createdProjects > 0) {
                $io->success(sprintf('Imported %s projects', $createdProjects));
            }
        }

        return 0;
    }
}
