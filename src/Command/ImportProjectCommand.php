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
use App\Validator\ValidationFailedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command can change anytime, don't rely on its API for the future!
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
            ->addOption('date-format', null, InputOption::VALUE_REQUIRED, 'Date format for imports', 'Y-m-d')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Timezone for imports', date_default_timezone_get())
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
            $tmpUser = $this->users->findOneBy(['username' => $teamlead]);
            if ($tmpUser === null) {
                $tmpUser = $this->users->findOneBy(['email' => $teamlead]);
                if ($tmpUser === null) {
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

            $teamlead = $tmpUser;
        }

        $skipUpdate = $input->getOption('no-update');
        $doImport = true;
        $row = 1;
        $errors = 0;
        $projects = [];

        try {
            $importer = $this->importerService->getProjectImporter($input->getOption('importer'));
            $reader = $this->importerService->getReader($input->getOption('reader'));
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return 1;
        }

        $importerFile = $input->getArgument('file');

        $io->text('Reading import file ...');

        try {
            $records = $reader->read($importerFile);
        } catch (ImportNotFoundException $ex) {
            $io->error('File not existing or not readable: ' . $importerFile);

            return 2;
        }

        $amount = iterator_count($records);
        $records->rewind();
        $io->text(sprintf('Found %s rows to process, converting now ...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        $options = [];
        if (null !== ($dateFormat = $input->getOption('date-format'))) {
            $options['dateformat'] = $dateFormat;
        }
        if (null !== ($timezone = $input->getOption('timezone'))) {
            $options['timezone'] = $timezone;
        }

        foreach ($records as $record) {
            try {
                $projects[] = $importer->convertEntryToProject($record, $options);
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

            return 3;
        }

        $createdProjects = 0;
        $updatedProjects = 0;
        $noUpdatedProjects = 0;
        $createdCustomers = 0;
        $createdTeams = 0;

        $amount = \count($projects);
        $io->text(sprintf('Converted %s projects, importing into Kimai now ...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        foreach ($projects as $project) {
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
                $createdTeams++;
            } catch (ValidationFailedException $ex) {
                $io->error(sprintf('Failed importing project "%s" with: %s', $project->getName(), $ex->getMessage()));
                for ($i = 0; $i < $ex->getViolations()->count(); $i++) {
                    $violation = $ex->getViolations()->get($i);
                    $io->error(sprintf('Failed validating field "%s" with value "%s": %s', $violation->getPropertyPath(), $violation->getInvalidValue(), $violation->getMessage()));
                }

                return 4;
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing project "%s" with: %s', $project->getName(), $ex->getMessage()));

                return 4;
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
            if ($createdTeams > 0) {
                $io->success(sprintf('Created %s teams', $createdTeams));
            }
        }

        return 0;
    }
}
