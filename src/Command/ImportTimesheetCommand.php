<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\SystemConfiguration;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Importer\InvalidFieldsException;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TagRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Utils\Duration;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * This command can change anytime, don't rely on its API for the future!
 *
 * @internal
 * @codeCoverageIgnore
 */
class ImportTimesheetCommand extends Command
{
    protected static $defaultName = 'kimai:import:timesheet';

    // if we use 00:00 we might run into summer/winter time problems which happen between 02:00 and 03:00
    public const DEFAULT_BEGIN = '04:00';
    public const DEFAULT_CUSTOMER = 'Imported customer - %s';

    private static $supportedHeader = [
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

    private $customers;
    private $projects;
    private $activities;
    private $users;
    private $tagRepository;
    private $timesheets;
    private $configuration;
    private $encoder;

    /**
     * @var Customer
     */
    private $customerFallback;
    /**
     * @var Customer[]
     */
    private $customerCache = [];
    /**
     * @var Project[]
     */
    private $projectCache = [];
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
    /**
     * @var string
     */
    private $begin = self::DEFAULT_BEGIN;
    // some statistics to display to the user
    private $createdProjects = 0;
    private $createdUsers = 0;
    private $createdCustomers = 0;
    private $createdActivities = 0;

    public function __construct(
        CustomerRepository $customers,
        ProjectRepository $projects,
        ActivityRepository $activities,
        UserRepository $users,
        TagRepository $tagRepository,
        TimesheetRepository $timesheets,
        SystemConfiguration $configuration,
        UserPasswordEncoderInterface $encoder
    ) {
        parent::__construct();
        $this->customers = $customers;
        $this->projects = $projects;
        $this->activities = $activities;
        $this->users = $users;
        $this->tagRepository = $tagRepository;
        $this->timesheets = $timesheets;
        $this->configuration = $configuration;
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Import timesheets from CSV file')
            ->setHelp(
                'This command allows to import timesheets from a CSV file, which are formatted like CSV exports.' . PHP_EOL .
                'Imported customer, projects and activities will be matched by name.' . PHP_EOL .
                'Supported columns names: ' . implode(', ', self::$supportedHeader) . PHP_EOL
            )
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'The timezone to be used. Supports: "valid timezone names", the string "user" (using the configured users timezone) and the string "server" (PHP default timezone)', 'user')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'A customer ID or name to assign for empty entries. Defaults to creating a new customer which is used for all un-linked projects')
            ->addOption('activity', null, InputOption::VALUE_OPTIONAL, 'Whether new activities should be "global" or "project" specific. Allowed values are "global" and "project"', 'project')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'The CSV field delimiter', ',')
            ->addOption('begin', null, InputOption::VALUE_OPTIONAL, 'Default begin if none was provided in the format HH:MM', self::DEFAULT_BEGIN)
            ->addOption('comment', null, InputOption::VALUE_OPTIONAL, 'A description to be added to created customers, projects and activities. %s will be replaced with the current datetime', 'Created by import at %s')
            ->addOption('create-users', null, InputOption::VALUE_NONE, 'If set, accounts for not found users will be created')
            ->addOption('ignore-errors', null, InputOption::VALUE_NONE, 'If set, invalid rows will be skipped')
            ->addOption('batch', null, InputOption::VALUE_NONE, 'If set, timesheets will be written in batches of 100')
            ->addOption('domain', null, InputOption::VALUE_OPTIONAL, 'Domain name used for email addresses of new created users. If provided usernames already include a domain, this option will be skipped.', 'example.com')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Password for new created users.', 'password')
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

        $io->title('Kimai importer: Timesheets');

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
        $this->begin = $input->getOption('begin');

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
        if (!\in_array($activityType, $allowedActivityTypes)) {
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
                    'Found invalid CSV. The header: ' . PHP_EOL .
                    '%s' . PHP_EOL .
                    'did not match the expected structure: ' . PHP_EOL .
                    '%s',
                    implode(', ', $header),
                    implode(', ', self::$supportedHeader)
                )
            );

            return 5;
        }

        $all = $csv->getRecords();
        $total = iterator_count($all);

        $io->text(sprintf('Found %s timesheets to import, validating now', $total));

        $records = [];
        $doImport = true;
        $row = 1;
        $errors = 0;

        $createUsers = $input->getOption('create-users');
        $ignoreErrors = $input->getOption('ignore-errors');

        // ======================= validate rows =======================
        $progressBar = new ProgressBar($output, $total);

        $countAll = 0;
        foreach ($all as $record) {
            $this->convertRow($record);
            try {
                $this->validateRow($record);
            } catch (InvalidFieldsException $ex) {
                $io->error(sprintf('Invalid row %s, invalid fields: %s', $row, implode(', ', $ex->getFields())));
                $doImport = false;
                $errors++;
            }

            if (!$createUsers) {
                if (null === $this->getUser($record['User'])) {
                    if (!$ignoreErrors) {
                        $io->error(sprintf('Unknown user %s in row %s', $record['User'], $row));
                    }
                    $doImport = false;
                    $errors++;
                }
            }

            $row++;

            if ($doImport) {
                $records[] = $record;
            }
            $countAll++;
            $progressBar->advance();
        }
        $progressBar->finish();

        if (!$ignoreErrors && !$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 5;
        }

        $io->text(sprintf('Validated %s rows.', $countAll));
        $io->text(sprintf('Importing %s of %s rows, skipping %s with validation errors.', \count($records), iterator_count($all), $errors));

        // values for new users
        $password = $input->getOption('password');
        $domain = $input->getOption('domain');

        $progressBar = new ProgressBar($output, \count($records));

        $durationParser = new Duration();
        $row = 0;

        $isBatchUpdate = $input->getOption('batch');
        $batches = [];

        foreach ($records as $record) {
            $row++;
            try {
                $project = $this->getProject($record['Project'], $record['Customer'], $input->getOption('customer'));
                $activity = $this->getActivity($record['Activity'], $project, $activityType);

                $user = $this->getUser($record['User']);
                if (null === $user) {
                    $user = $this->createUser($record['User'], $domain, $password);
                }

                $begin = null;
                $end = null;
                $duration = 0;

                if (!empty($record['Duration'])) {
                    if (\is_int($record['Duration'])) {
                        $duration = $record['Duration'];
                    } else {
                        $duration = $durationParser->parseDurationString($record['Duration']);
                    }
                }

                if (null === $timezone) {
                    $timezone = new \DateTimeZone($user->getTimezone());
                }

                if (empty($record['From']) && empty($record['To'])) {
                    $begin = new \DateTime($record['Date'] . ' ' . $this->begin, $timezone);
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

                    // fix dates, which are running over midnight
                    if ($end < $begin) {
                        if ($duration > 0) {
                            $end = (new \DateTime())->setTimezone($timezone)->setTimestamp($begin->getTimestamp() + $duration);
                        } else {
                            $end->add(new \DateInterval('P1D'));
                        }
                    }
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
                    foreach (explode(',', $record['Tags']) as $tagName) {
                        if (empty($tagName)) {
                            continue;
                        }

                        if (null === ($tag = $this->tagRepository->findTagByName($tagName))) {
                            $tag = (new Tag())->setName($tagName);
                        }

                        $timesheet->addTag($tag);
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

                if ($isBatchUpdate) {
                    $batches[] = $timesheet;

                    if ($row % 100 === 0) {
                        $this->timesheets->saveMultiple($batches);
                        $batches = [];
                    }
                } else {
                    $this->timesheets->save($timesheet);
                }
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing timesheet row %s with: %s', $row, $ex->getMessage()));

                return 6;
            }

            $progressBar->advance();
        }

        if ($isBatchUpdate && \count($batches) > 0) {
            $this->timesheets->saveMultiple($batches);
        }

        $progressBar->finish();

        if ($this->createdUsers > 0) {
            $io->success(sprintf('Created %s users', $this->createdUsers));
        }
        if ($this->createdCustomers > 0) {
            $io->success(sprintf('Created %s customers', $this->createdCustomers));
        }
        if ($this->createdProjects > 0) {
            $io->success(sprintf('Created %s projects', $this->createdProjects));
        }
        if ($this->createdActivities > 0) {
            $io->success(sprintf('Created %s activities', $this->createdActivities));
        }

        $io->success(sprintf('Imported %s rows', $row));

        return 0;
    }

    private function createUser($username, $domain, $password): User
    {
        $user = new User();
        $user->setUsername($username);
        if (stripos($username, '@') === false) {
            $email = preg_replace('/[[:^print:]]/', '', $username) . '@' . $domain;
            $email = strtolower($email);
        } else {
            $email = $username;
        }
        $user->setEmail($email);
        $user->setPassword($this->encoder->encodePassword($user, $password));

        $this->users->saveUser($user);
        $this->createdUsers++;

        $this->userCache[$username] = $user;

        return $user;
    }

    private function getUser($user): ?User
    {
        if (!\array_key_exists($user, $this->userCache)) {
            $tmpUser = $this->users->findOneBy(['username' => $user]);
            if (null === $tmpUser) {
                $tmpUser = $this->users->findOneBy(['email' => $user]);
                if (null === $tmpUser) {
                    return null;
                }
            }
            $this->userCache[$user] = $tmpUser;
        }

        return $this->userCache[$user];
    }

    private function getActivity($activity, Project $project, $activityType): Activity
    {
        $tmpActivity = null;

        $tmpActivities = $this->activities->findBy(['project' => $project, 'name' => $activity]);

        if (\count($tmpActivities) === 0) {
            $tmpActivity = $this->activities->findOneBy(['project' => null, 'name' => $activity]);
        } elseif (\count($tmpActivities) === 1) {
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
            $this->createdActivities++;
        }

        return $tmpActivity;
    }

    private function getProject($project, $customer, $fallbackCustomer): Project
    {
        if (!\array_key_exists($project, $this->projectCache)) {
            /** @var Customer $tmpCustomer */
            $tmpCustomer = $this->getCustomer($customer, $fallbackCustomer);
            /** @var Project $tmpProject */
            $tmpProject = null;
            /** @var Project[] $tmpProjects */
            $tmpProjects = $this->projects->findBy(['name' => $project]);

            if (\count($tmpProjects) > 1) {
                /** @var Project $prj */
                foreach ($tmpProjects as $prj) {
                    if (strcasecmp($prj->getCustomer()->getName(), $tmpCustomer->getName()) !== 0) {
                        continue;
                    }
                    $tmpProject = $prj;
                    break;
                }
            } elseif (\count($tmpProjects) === 1) {
                $tmpProject = $tmpProjects[0];
            }

            if (null !== $tmpProject) {
                if (strcasecmp($tmpProject->getCustomer()->getName(), $tmpCustomer->getName()) !== 0) {
                    $tmpProject = null;
                }
            }

            if ($tmpProject === null) {
                $tmpProject = new Project();
                $tmpProject->setName($project);
                $tmpProject->setComment($this->comment);
                $tmpProject->setCustomer($tmpCustomer);
                $this->projects->saveProject($tmpProject);
                $this->createdProjects++;
            }

            $this->projectCache[$project] = $tmpProject;
        }

        return $this->projectCache[$project];
    }

    private function getCustomer($customer, $fallback): Customer
    {
        if (!empty($customer)) {
            if (!\array_key_exists($customer, $this->customerCache)) {
                $tmpCustomer = $this->customers->findBy(['name' => $customer]);
                if (\count($tmpCustomer) > 1) {
                    throw new \Exception(sprintf('Found multiple customers with the name: %s', $customer));
                } elseif (\count($tmpCustomer) === 1) {
                    $tmpCustomer = $tmpCustomer[0];
                }

                if ($tmpCustomer instanceof Customer) {
                    $this->customerCache[$customer] = $tmpCustomer;
                }
            }

            if (\array_key_exists($customer, $this->customerCache)) {
                return $this->customerCache[$customer];
            }
        }

        if (null === $this->customerFallback) {
            $tmpFallback = null;

            if (!empty($fallback)) {
                if (\is_int($customer)) {
                    $tmpFallback = $this->customers->find($fallback);
                } else {
                    /** @var Customer|null $tmpFallback */
                    $tmpFallback = $this->customers->findOneBy(['name' => $fallback]);
                }
            }

            if (null === $tmpFallback) {
                $newName = $customer;
                if (empty($customer)) {
                    $newName = self::DEFAULT_CUSTOMER;
                    if (!empty($fallback) && \is_string($fallback)) {
                        $newName = $fallback;
                    }
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
                $this->createdCustomers++;
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
        $result = array_diff(self::$supportedHeader, $header);

        return empty($result);
    }

    /**
     * Add project specific conversion logic here
     *
     * @param array $row
     */
    private function convertRow(array &$row)
    {
        // negative durations
        if ($row['Duration'][0] === '-') {
            $row['Duration'] = substr($row['Duration'], 1);
        }

        if (!\array_key_exists('Tags', $row)) {
            $row['Tags'] = null;
        }
        if (empty($row['Date'])) {
            $row['Date'] = '1970-01-01';
        }
        if (!\array_key_exists('Exported', $row)) {
            $row['Exported'] = false;
        }
        if (!\array_key_exists('Rate', $row)) {
            $row['Rate'] = null;
        }
        if (!\array_key_exists('Hourly rate', $row)) {
            $row['Hourly rate'] = null;
        }
        if (!\array_key_exists('Fixed rate', $row)) {
            $row['Fixed rate'] = null;
        }
        if (!empty($row['From'])) {
            $len = \strlen($row['From']);
            if ($len === 1) {
                $row['From'] = '0' . $row['From'] . ':00';
            } elseif ($len == 2) {
                $row['From'] = $row['From'] . ':00';
            }
        }
        if (!empty($row['To'])) {
            $len = \strlen($row['To']);
            if ($len === 1) {
                $row['To'] = '0' . $row['To'] . ':00';
            } elseif ($len == 2) {
                $row['To'] = $row['To'] . ':00';
            }
        }
    }
}
