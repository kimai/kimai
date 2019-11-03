<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\FormConfiguration;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Importer\InvalidFieldsException;
use App\Importer\UnknownUserException;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Utils\Duration;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command can change anytime or even get deleted, don't rely on it!
 *
 * @internal 
 * @codeCoverageIgnore
 */
class CsvImporterCommand extends Command
{
    protected static $defaultName = 'kimai:csv-importer';

    public const DEFAULT_BEGIN = '00:00';
    public const DEFAULT_CUSTOMER = 'Imported customer - %s';

    private $supportedHeader = [
        'Date',
        'From',
        'To',
        'Duration',
        'Rate',
        'User',
        'Customer',
        'Project',
        'Activity',
        'Description',
        'Exported',
        'Tags',
        'Hourly rate',
        'Fixed rate',
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
     * @var ActivityRepository
     */
    private $activities;
    /**
     * @var UserRepository
     */
    private $users;
    /**
     * @var TimesheetRepository
     */
    private $timesheets;
    /**
     * @var FormConfiguration
     */
    private $configuration;
    /**
     * @var Customer
     */
    private $customerFallback;
    /**
     * @var Customer[]
     */
    private $customerCache = [];
    /**
     * @var User[]
     */
    private $userCache = [];
    /**
     * Comment that will be added to new customers, projects and activities.
     *
     * @var string
     */
    private $comment = '';
    /**
     * The datetime of this import as formatted string.
     *
     * @var string
     */
    private $dateTime = '';

    public function __construct(CustomerRepository $customers, ProjectRepository $projects, ActivityRepository $activities, UserRepository $users, TimesheetRepository $timesheets, FormConfiguration $configuration)
    {
        parent::__construct();
        $this->customers = $customers;
        $this->projects = $projects;
        $this->activities = $activities;
        $this->users = $users;
        $this->timesheets = $timesheets;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Basic CSV importer for Kimai')
            ->setHelp('This command helps to import CSV files, which are formatted like CSV exports. Imported customer, projects and activities will be matched by name.')
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'The timezone to be used. Supports: valid timezone names and the string "user" (using the configured users timezone) and the string "server" (PHP default timezone)', 'user')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'A customer ID to assign for empty entries. Defaults to creating a new customer which is used for all un-linked projects')
            ->addOption('activity', null, InputOption::VALUE_OPTIONAL, 'Whether new activities should be "global" or "project" specific. Allowed values are "global" and "project". By default project specific activities are created.', 'project')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'The CSV field delimiter', ',')
            ->addOption('comment', null, InputOption::VALUE_OPTIONAL, 'A description to be added to created customers, projects and activities. %s will be replaced with the current datetime', 'Imported at %s')
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

        $io->title('Kimai CSV Importer');

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

        $timezone = $input->getOption('timezone');
        switch ($timezone) {
            case 'server':
                $timezone = new \DateTimeZone(date_default_timezone_get());
                break;

            case 'user':
                // null means fetch from user
                $timezone = null;
                break;

            default:
                try {
                    $timezone = new \DateTimeZone($timezone);
                } catch (\Exception $ex) {
                    $io->error('Invalid timezone given, import canceled.');

                    return 3;
                }
                break;
        }

        $activityType = $input->getOption('activity');
        $allowedActivityTypes = ['project', 'global'];
        if (!in_array($activityType, $allowedActivityTypes)) {
            $io->error(sprintf('Invalid activity type "%s" given, allowed values are: %s', $activityType, implode(', ', $allowedActivityTypes)));

            return 4;
        }

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setDelimiter($input->getOption('delimiter'));
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        if (!$this->validateHeader($header)) {
            $io->error(
                sprintf(
                    'Found invalid CSV. The header:' . PHP_EOL . '%s' . PHP_EOL . 'did not match the expected structure: ' . PHP_EOL . '%s',
                    implode(', ', $header),
                    implode(', ', $this->supportedHeader)
                )
            );

            return 5;
        }

        $records = $csv->getRecords();

        $doImport = true;
        $row = 1;
        $errors = 0;

        // ======================= validate rows =======================
        foreach ($records as $record) {
            try {
                $this->validateRow($record);
            } catch (InvalidFieldsException $ex) {
                $io->error(sprintf('Invalid row %s, invalid fields: %s', $row, implode(', ', $ex->getFields())));
                $doImport = false;
                $errors++;
            }

            try {
                $user = $this->getUser($record['User']);
            } catch (UnknownUserException $ex) {
                $io->error(sprintf('Unknown user %s in row %s', $ex->getUsername(), $row));
                $doImport = false;
                $errors++;
            }

            $row++;
        }

        if (!$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 5;
        }

        $durationParser = new Duration();
        $row = 0;
        foreach ($records as $record) {
            $row++;
            try {
                $project = $this->getProject($record['Project'], $record['Customer'], $input->getOption('customer'));
                $activity = $this->getActivity($record['Activity'], $project, $activityType);
                $user = $this->getUser($record['User']);

                $begin = null;
                $end = null;
                $duration = 0;

                if (!empty($record['Duration'])) {
                    if (is_int($record['Duration'])) {
                        $duration = $record['Duration'];
                    } else {
                        $duration = $durationParser->parseDurationString($record['Duration']);
                    }
                }

                if (null === $timezone) {
                    $timezone = new \DateTimeZone($user->getTimezone());
                }

                if (empty($record['From']) && empty($record['To'])) {
                    $begin = new \DateTime($record['Date'] . ' ' . self::DEFAULT_BEGIN, $timezone);
                    $end = (new \DateTime())->setTimezone($timezone)->setTimestamp($begin->getTimestamp() + $duration);
                } elseif (empty($record['From'])) {
                    $end = new \DateTime($record['Date'] . ' ' . $record['To'], $timezone);
                    $begin = (new \DateTime())->setTimezone($timezone)->setTimestamp($end->getTimestamp() - $duration);
                } elseif (empty($record['To'])) {
                    $begin = new \DateTime($record['Date'] . ' ' . $record['From'], $timezone);
                    $end = (new \DateTime())->setTimezone($timezone)->setTimestamp($begin->getTimestamp() + $duration);
                } else {
                    $begin = new \DateTime($record['Date'] . ' ' . $record['From'], $timezone);
                    $end = new \DateTime($record['Date'] . ' ' . $record['To'], $timezone);
                }

                $timesheet = new Timesheet();
                $timesheet->setActivity($activity);
                $timesheet->setProject($project);
                $timesheet->setBegin($begin);
                $timesheet->setEnd($end);
                $timesheet->setUser($user);
                $timesheet->setDescription($record['Description']);
                $timesheet->setExported((bool) $record['Exported']);

                if (!empty($record['Tags'])) {
                    foreach (explode(',', $record['Tags']) as $tag) {
                        if (empty($tag)) {
                            continue;
                        }
                        $timesheet->addTag((new Tag())->setName($tag));
                    }
                }

                if (!empty($record['Rate'])) {
                    $timesheet->setRate($record['Rate']);
                }
                if (!empty($record['Hourly rate'])) {
                    $timesheet->setHourlyRate($record['Hourly rate']);
                }
                if (!empty($record['Fixed rate'])) {
                    $timesheet->setFixedRate($record['Fixed rate']);
                }

                $this->timesheets->save($timesheet);
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing timesheet row %s with: %s', $row, $ex->getMessage()));

                return 6;
            }
        }

        $io->success(sprintf('Imported %s rows', $row));

        return 0;
    }

    private function getUser($user): User
    {
        if (!array_key_exists($user, $this->userCache)) {
            $tmpUser = $this->users->loadUserByUsername($user);
            if (null === $tmpUser) {
                throw new UnknownUserException($user);
            }
            $this->userCache[$user] = $tmpUser;
        }

        return $this->userCache[$user];
    }

    private function getActivity($activity, Project $project, $activityType): Activity
    {
        $tmpActivity = null;

        $tmpActivities = $this->activities->findBy(['project' => $project, 'name' => $activity]);

        if (count($tmpActivities) === 0) {
            $tmpActivity = $this->activities->findOneBy(['project' => null, 'name' => $activity]);
        } elseif (count($tmpActivities) === 1) {
            $tmpActivity = $tmpActivities[0];
        }

        if (null === $tmpActivity) {
            $tmpActivity = new Activity();
            $tmpActivity->setName($activity);
            $tmpActivity->setComment($this->comment);
            if ($activityType === 'project') {
                $tmpActivity->setProject($project);
            }
            $this->activities->saveActivity($tmpActivity);
        }

        return $tmpActivity;
    }

    private function getProject($project, $customer, $fallbackCustomer): Project
    {
        /** @var Customer $tmpCustomer */
        $tmpCustomer = $this->getCustomer($customer, $fallbackCustomer);
        /** @var Project $tmpProject */
        $tmpProject = null;
        /** @var Project[] $tmpProjects */
        $tmpProjects = $this->projects->findBy(['name' => $project]);

        if (count($tmpProjects) > 1) {
            /** @var Project $prj */
            foreach ($tmpProjects as $prj) {
                if ($prj->getCustomer()->getName() !== $tmpCustomer->getName()) {
                    continue;
                }
                $tmpProject = $prj;
                break;
            }
        } elseif (count($tmpProjects) === 1) {
            $tmpProject = $tmpProjects[0];
        }

        if (null !== $tmpProject) {
            if ($tmpProject->getCustomer()->getName() !== $tmpCustomer->getName()) {
                $tmpProject = null;
            }
        }

        if ($tmpProject === null) {
            $tmpProject = new Project();
            $tmpProject->setName($project);
            $tmpProject->setComment($this->comment);
            $tmpProject->setCustomer($tmpCustomer);
            $this->projects->saveProject($tmpProject);
        }

        return $tmpProject;
    }

    private function getCustomer($customer, $fallback): Customer
    {
        if (!empty($customer)) {
            if (!array_key_exists($customer, $this->customerCache)) {
                $tmpCustomer = $this->customers->findBy(['name' => $customer]);
                if (count($tmpCustomer) > 1) {
                    throw new \Exception(sprintf('Found multiple customers with the name: %s', $customer));
                } elseif (count($tmpCustomer) === 1) {
                    $tmpCustomer = $tmpCustomer[0];
                }

                if (null !== $tmpCustomer) {
                    $this->customerCache[$customer] = $tmpCustomer;
                }
            }

            if (array_key_exists($customer, $this->customerCache)) {
                return $this->customerCache[$customer];
            }
        }

        if (null === $this->customerFallback) {
            $tmpFallback = null;

            if (!empty($fallback)) {
                if (is_int($customer)) {
                    $tmpFallback = $this->customers->find($fallback);
                } else {
                    $tmpFallback = $this->customers->findOneBy(['name' => $fallback]);
                }
            }

            if (null === $tmpFallback) {
                $newName = self::DEFAULT_CUSTOMER;
                if (!empty($fallback) && is_string($fallback)) {
                    $newName = $fallback;
                }
                $tmpFallback = new Customer();
                $tmpFallback->setName(sprintf($newName, $this->dateTime));
                $tmpFallback->setComment($this->comment);
                $tmpFallback->setCountry($this->configuration->getCustomerDefaultCountry());
                $timezone = date_default_timezone_get();
                if (null !== $this->configuration->getCustomerDefaultTimezone()) {
                    $timezone = $this->configuration->getCustomerDefaultTimezone();
                }
                $tmpFallback->setTimezone($timezone);
                $this->customers->saveCustomer($tmpFallback);
            }

            $this->customerFallback = $tmpFallback;
        }

        return $this->customerFallback;
    }

    /**
     * @param array $row
     * @return bool
     * @throws InvalidFieldsException
     */
    private function validateRow(array $row)
    {
        $fields = [];

        if (empty($row['Project'])) {
            $fields[] = 'Project';
        }

        if (empty($row['Activity'])) {
            $fields[] = 'Activity';
        }

        if (empty($row['Date'])) {
            $fields[] = 'Date';
        }

        if ((empty($row['From']) || empty($row['To'])) && empty($row['Duration'])) {
            $fields[] = 'Duration';
        }

        if (!empty($fields)) {
            throw new InvalidFieldsException($fields);
        }

        return true;
    }

    private function validateHeader(array $header)
    {
        $result = array_diff($this->supportedHeader, $header);

        return empty($result);
    }
}
