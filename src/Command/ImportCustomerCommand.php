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
use App\Entity\Project;
use App\Entity\Team;
use App\Importer\InvalidFieldsException;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
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
class ImportCustomerCommand extends Command
{
    protected static $defaultName = 'kimai:import:customer';

    private static $requiredHeader = [
        'Name',
        'Customer',
    ];

    private static $supportedHeader = [
        'Name',
        'Customer',
        'Comment',
        'OrderNumber',
        'OrderDate',
    ];

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
            ->setName(self::$defaultName)
            ->setDescription('Import projects from CSV file')
            ->setHelp(
                'This command allows to import projects from a CSV file, creating customers (if not existing) and optional empty teams for each project.' . PHP_EOL .
                'Imported customer will be matched by name and optionally created on the fly.' . PHP_EOL .
                'Required column names: ' . implode(', ', self::$requiredHeader) . PHP_EOL .
                'Supported column names: ' . implode(', ', self::$supportedHeader) . PHP_EOL
            )
            ->addOption('teamlead', null, InputOption::VALUE_REQUIRED, 'If you want to create empty teams for each project, give the username of the teamlead to be assigned')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'The CSV field delimiter', ',')
            ->addOption('comment', null, InputOption::VALUE_OPTIONAL, 'A description to be added to created customers and projects. %s will be replaced with the current datetime', 'Imported at %s')
            ->addArgument('file', InputArgument::REQUIRED, 'The CSV file to be imported')
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

        $csvFile = $input->getArgument('file');
        if (!file_exists($csvFile)) {
            $io->error('File not existing: ' . $csvFile);

            return 1;
        }

        if (!is_readable($csvFile)) {
            $io->error('File cannot be read: ' . $csvFile);

            return 2;
        }

        $this->dateTime = (new \DateTime())->format('Y.m.d H:i');
        $this->comment = sprintf($input->getOption('comment'), $this->dateTime);

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setDelimiter($input->getOption('delimiter'));
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();

        // validate teamlead
        $teamlead = $input->getOption('teamlead');
        if (null !== $teamlead) {
            $tmpUser = $this->users->findOneBy(['username' => $teamlead]);
            if (null === $tmpUser) {
                $tmpUser = $this->users->findOneBy(['email' => $teamlead]);
                if (null === $tmpUser) {
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

        if (!$this->validateHeader($header)) {
            $io->error(
                sprintf(
                    'Found invalid CSV header: %s' . PHP_EOL .
                    'Required fields: %s' . PHP_EOL .
                    'All supported fields: %s' . PHP_EOL,
                    implode(', ', $header),
                    implode(', ', self::$requiredHeader),
                    implode(', ', self::$supportedHeader)
                )
            );

            return 4;
        }

        $records = $csv->getRecords();

        $doImport = true;
        $row = 1;
        $errors = 0;

        foreach ($records as $record) {
            try {
                $this->validateRow($record);
            } catch (InvalidFieldsException $ex) {
                $io->error(sprintf('Invalid row %s, invalid fields: %s', $row, implode(', ', $ex->getFields())));
                $doImport = false;
                $errors++;
            }

            $row++;
        }

        if (!$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 5;
        }

        $row = 0;
        foreach ($records as $record) {
            $row++;
            try {
                $customer = $this->getCustomer($record['Customer']);
                $projectName = $record['Name'];

                $project = new Project();
                $project->setName($projectName);
                $project->setCustomer($customer);

                $comment = $this->comment;
                if (isset($record['Comment']) && !empty($record['Comment'])) {
                    $comment = $record['Comment'];
                }
                $project->setComment($comment);

                if (isset($record['OrderNumber']) && !empty($record['OrderNumber'])) {
                    $project->setOrderNumber($record['OrderNumber']);
                }
                if (isset($record['OrderDate']) && !empty($record['OrderDate'])) {
                    $project->setOrderDate($record['OrderDate']);
                }

                $team = null;
                if (null !== $teamlead) {
                    $team = new Team();
                    $team->setName($projectName);
                    $team->setTeamLead($teamlead);

                    $this->teams->saveTeam($team);
                }

                $this->projects->saveProject($project);

                if (null !== $team) {
                    $project->addTeam($team);
                    $team->addProject($project);

                    $this->teams->saveTeam($team);
                    $this->projects->saveProject($project);
                }
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing project row %s with: %s', $row, $ex->getMessage()));

                return 6;
            }
        }

        $io->success(sprintf('Imported %s rows', $row));

        return 0;
    }

    private function getCustomer(string $customerName): Customer
    {
        if (!\array_key_exists($customerName, $this->customerCache)) {
            $tmpCustomer = $this->customers->findBy(['name' => $customerName]);

            if (\count($tmpCustomer) > 1) {
                throw new \Exception(sprintf('Found multiple customers with the name: %s', $customerName));
            } elseif (\count($tmpCustomer) === 1) {
                $tmpCustomer = $tmpCustomer[0];
            }

            if ($tmpCustomer instanceof Customer) {
                $this->customerCache[$customerName] = $tmpCustomer;
            }
        }

        if (\array_key_exists($customerName, $this->customerCache)) {
            return $this->customerCache[$customerName];
        }

        $customer = new Customer();
        $customer->setName(sprintf($customerName, $this->dateTime));
        $customer->setComment($this->comment);
        $customer->setCountry($this->configuration->getCustomerDefaultCountry());
        $timezone = date_default_timezone_get();
        if (null !== $this->configuration->getCustomerDefaultTimezone()) {
            $timezone = $this->configuration->getCustomerDefaultTimezone();
        }
        $customer->setTimezone($timezone);

        $this->customers->saveCustomer($customer);

        $this->customerCache[$customerName] = $customer;

        return $customer;
    }

    /**
     * @param array $row
     * @return bool
     * @throws InvalidFieldsException
     */
    private function validateRow(array $row)
    {
        $fields = [];

        foreach (self::$requiredHeader as $headerName) {
            if (!isset($row[$headerName]) || empty($row[$headerName])) {
                $fields[] = $headerName;
            }
        }

        if (!empty($fields)) {
            throw new InvalidFieldsException($fields);
        }

        return true;
    }

    private function validateHeader(array $header)
    {
        $fields = [];

        foreach (self::$requiredHeader as $headerName) {
            if (!\in_array($headerName, $header)) {
                $fields[] = $headerName;
            }
        }

        return empty($fields);
    }
}
