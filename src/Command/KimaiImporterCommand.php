<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Doctrine\TimesheetSubscriber;
use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\ActivityRate;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\ProjectRate;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Timesheet\Util;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command used to import data from a Kimai v1 installation.
 * Getting help in improving this script would be fantastic, it currently only handles the most basic use-cases.
 *
 * This command is way to messy and complex to be tested ... so we use something, which I actually don't like:
 * @codeCoverageIgnore
 */
final class KimaiImporterCommand extends Command
{
    // minimum required Kimai and database version, lower versions are not supported by this command
    public const MIN_VERSION = '1.0.1';
    public const MIN_REVISION = '1388';

    /**
     * Create the user default passwords
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * Validates the entities before they will be created
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * Connection to the Kimai v2 database to write imported data to
     * @var ManagerRegistry
     */
    private $doctrine;
    /**
     * Connection to the old database to import data from
     * @var Connection
     */
    private $connection;
    /**
     * Prefix for the v1 database tables.
     * @var string
     */
    private $dbPrefix = '';
    /**
     * Old UserID => new User()
     * @var User[]
     */
    private $users = [];
    /**
     * @var Customer[]
     */
    private $customers = [];
    /**
     * Old Project ID => new Project()
     * @var Project[]
     */
    private $projects = [];
    /**
     * id => [projectId => Activity]
     * @var array<Activity[]>
     */
    private $activities = [];
    /**
     * @var Team[]
     */
    protected $teams = [];
    /**
     * @var bool
     */
    private $debug = false;
    /**
     * @var array
     */
    private $oldActivities = [];

    public function __construct(UserPasswordEncoderInterface $encoder, ManagerRegistry $registry, ValidatorInterface $validator)
    {
        $this->encoder = $encoder;
        $this->doctrine = $registry;
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:import-v1')
            ->setDescription('Import data from a Kimai v1 installation')
            ->setHelp('This command allows you to import the most important data from a Kimi v1 installation.')
            ->addArgument(
                'connection',
                InputArgument::REQUIRED,
                'The database connection as URL, e.g.: mysql://user:password@127.0.0.1:3306/kimai?charset=utf8'
            )
            ->addArgument('prefix', InputArgument::REQUIRED, 'The database prefix for the old Kimai v1 tables')
            ->addArgument('password', InputArgument::REQUIRED, 'The new password for all imported user')
            ->addArgument('country', InputArgument::OPTIONAL, 'The default country for customer (2-character uppercase)', 'DE')
            ->addArgument('currency', InputArgument::OPTIONAL, 'The default currency for customer (code like EUR, CHF, GBP or USD)', 'EUR')
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'Default timezone for imported users', date_default_timezone_get())
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'Default language for imported users', User::DEFAULT_LANGUAGE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // do not convert the times, Kimai 1 stored them already in UTC
        Type::overrideType(Types::DATETIME_MUTABLE, DateTimeType::class);

        // don't calculate rates ... this was done in Kimai 1
        $this->deactivateLifecycleCallbacks($this->getDoctrine()->getConnection());

        $io = new SymfonyStyle($input, $output);

        $config = new Configuration();
        $connectionParams = ['url' => $input->getArgument('connection')];
        $this->connection = DriverManager::getConnection($connectionParams, $config);

        $this->dbPrefix = $input->getArgument('prefix');

        $password = $input->getArgument('password');
        if (null === $password || \strlen($password = trim($password)) < 8) {
            $io->error('Password length is not sufficient, at least 8 character are required');

            return 1;
        }

        $country = $input->getArgument('country');
        if (null === $country || 2 != \strlen($country = trim($country))) {
            $io->error('Country code needs to be exactly 2 character');

            return 1;
        }

        $currency = $input->getArgument('currency');
        if (null === $currency || 3 != \strlen($currency = trim($currency))) {
            $io->error('Currency code needs to be exactly 3 character');

            return 1;
        }

        if (!$this->checkDatabaseVersion($io, self::MIN_VERSION, self::MIN_REVISION)) {
            return 1;
        }

        $bytesStart = memory_get_usage(true);

        // pre-load all data to make sure we can fully import everything
        try {
            $users = $this->fetchAllFromImport('users');
        } catch (Exception $ex) {
            $io->error('Failed to load users: ' . $ex->getMessage());

            return 1;
        }

        try {
            $customer = $this->fetchAllFromImport('customers');
        } catch (Exception $ex) {
            $io->error('Failed to load customers: ' . $ex->getMessage());

            return 1;
        }

        try {
            $projects = $this->fetchAllFromImport('projects');
        } catch (Exception $ex) {
            $io->error('Failed to load projects: ' . $ex->getMessage());

            return 1;
        }

        try {
            $activities = $this->fetchAllFromImport('activities');
        } catch (Exception $ex) {
            $io->error('Failed to load activities: ' . $ex->getMessage());

            return 1;
        }

        try {
            $activityToProject = $this->fetchAllFromImport('projects_activities');
        } catch (Exception $ex) {
            $io->error('Failed to load activities-project mapping: ' . $ex->getMessage());

            return 1;
        }

        try {
            $records = $this->fetchAllFromImport('timeSheet');
        } catch (Exception $ex) {
            $io->error('Failed to load timeSheet: ' . $ex->getMessage());

            return 1;
        }

        try {
            $fixedRates = $this->fetchAllFromImport('fixedRates');
        } catch (Exception $ex) {
            $io->error('Failed to load fixedRates: ' . $ex->getMessage());

            return 1;
        }

        try {
            $rates = $this->fetchAllFromImport('rates');
        } catch (Exception $ex) {
            $io->error('Failed to load rates: ' . $ex->getMessage());

            return 1;
        }

        try {
            $groups = $this->fetchAllFromImport('groups');
        } catch (Exception $ex) {
            $io->error('Failed to load groups: ' . $ex->getMessage());

            return 1;
        }

        try {
            $groupToCustomer = $this->fetchAllFromImport('groups_customers');
        } catch (Exception $ex) {
            $io->error('Failed to load groups-customers mappings: ' . $ex->getMessage());

            return 1;
        }

        try {
            $groupToProject = $this->fetchAllFromImport('groups_projects');
        } catch (Exception $ex) {
            $io->error('Failed to load groups-projects mappings: ' . $ex->getMessage());

            return 1;
        }

        try {
            $groupToUser = $this->fetchAllFromImport('groups_users');
        } catch (Exception $ex) {
            $io->error('Failed to load groups-users mappings: ' . $ex->getMessage());

            return 1;
        }

        $bytesCached = memory_get_usage(true);

        $io->success('Fetched Kimai v1 data, validating now ...');
        $validationMessages = [];
        try {
            $usedEmails = [];
            foreach ($users as $oldUser) {
                if (empty($oldUser['mail'])) {
                    $validationMessages[] = sprintf('User "%s" with ID %s has no email', $oldUser['name'], $oldUser['userID']);
                    continue;
                }
                if (\in_array($oldUser['mail'], $usedEmails)) {
                    $validationMessages[] = sprintf('Email "%s" for user "%s" with ID %s is already used', $oldUser['mail'], $oldUser['name'], $oldUser['userID']);
                }
                $usedEmails[] = $oldUser['mail'];
            }

            $customerIds = [];
            foreach ($customer as $oldCustomer) {
                $customerIds[] = $oldCustomer['customerID'];
            }

            foreach ($projects as $oldProject) {
                if (!\in_array($oldProject['customerID'], $customerIds)) {
                    $validationMessages[] = sprintf('Project "%s" with ID %s has unknown customer with ID %s', $oldProject['name'], $oldProject['projectID'], $oldProject['customerID']);
                }
            }
        } catch (Exception $ex) {
            $validationMessages[] = $ex->getMessage();
        }

        if (!empty($validationMessages)) {
            foreach ($validationMessages as $errorMessage) {
                $io->error($errorMessage);
            }

            return 1;
        }

        $io->success('Pre-validated data, trying to import now ...');

        $allImports = 0;

        try {
            $counter = $this->importUsers($io, $password, $users, $rates, $input->getOption('timezone'), $input->getOption('language'));
            $allImports += $counter;
            $io->success('Imported users: ' . $counter);
        } catch (Exception $ex) {
            $io->error('Failed to import users: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            return 1;
        }

        try {
            $counter = $this->importCustomers($io, $customer, $country, $currency);
            $allImports += $counter;
            $io->success('Imported customers: ' . $counter);
        } catch (Exception $ex) {
            $io->error('Failed to import customers: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            return 1;
        }

        try {
            $counter = $this->importProjects($io, $projects, $fixedRates, $rates);
            $allImports += $counter;
            $io->success('Imported projects: ' . $counter);
        } catch (Exception $ex) {
            $io->error('Failed to import projects: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            return 1;
        }

        try {
            $counter = $this->importActivities($io, $activities, $activityToProject, $fixedRates, $rates);
            $allImports += $counter;
            $io->success('Imported activities: ' . $counter);
        } catch (Exception $ex) {
            $io->error('Failed to import activities: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            return 1;
        }

        try {
            $counter = $this->importGroups($io, $groups, $groupToCustomer, $groupToProject, $groupToUser);
            $allImports += $counter;
            $io->success('Imported groups/teams: ' . $counter);
        } catch (Exception $ex) {
            $io->error('Failed to import groups/teams: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            return 1;
        }

        try {
            $counter = $this->importTimesheetRecords($io, $records, $fixedRates, $rates);
            $allImports += $counter;
            $io->success('Imported timesheet records: ' . $counter);
        } catch (Exception $ex) {
            $io->error('Failed to import timesheet records: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            return 1;
        }

        $bytesImported = memory_get_usage(true);

        $io->success(
            'Memory usage: ' . PHP_EOL .
            'Start: ' . $this->bytesHumanReadable($bytesStart) . PHP_EOL .
            'After caching: ' . $this->bytesHumanReadable($bytesCached) . PHP_EOL .
            'After import: ' . $this->bytesHumanReadable($bytesImported) . PHP_EOL .
            'Total consumption for importing ' . $allImports . ' new database entries: ' .
            $this->bytesHumanReadable($bytesImported - $bytesStart)
        );

        return 0;
    }

    /**
     * Checks if the given database connection for import has an underlying database with a compatible structure.
     * This is checked against the Kimai version and database revision.
     *
     * @param SymfonyStyle $io
     * @param string $requiredVersion
     * @param string $requiredRevision
     * @return bool
     */
    protected function checkDatabaseVersion(SymfonyStyle $io, $requiredVersion, $requiredRevision)
    {
        $optionColumn = $this->connection->quoteIdentifier('option');
        $qb = $this->connection->createQueryBuilder();

        $version = $this->connection->createQueryBuilder()
            ->select('value')
            ->from($this->connection->quoteIdentifier($this->dbPrefix . 'configuration'))
            ->where($qb->expr()->eq($optionColumn, ':option'))
            ->setParameter('option', 'version')
            ->execute()
            ->fetchColumn();

        $revision = $this->connection->createQueryBuilder()
            ->select('value')
            ->from($this->connection->quoteIdentifier($this->dbPrefix . 'configuration'))
            ->where($qb->expr()->eq($optionColumn, ':option'))
            ->setParameter('option', 'revision')
            ->execute()
            ->fetchColumn();

        if (1 == version_compare($requiredVersion, $version)) {
            $io->error(
                'Import can only performed from an up-to-date Kimai version:' . PHP_EOL .
                'Needs at least ' . $requiredVersion . ' but found ' . $version
            );

            return false;
        }

        if (1 == version_compare($requiredRevision, $revision)) {
            $io->error(
                'Import can only performed from an up-to-date Kimai version:' . PHP_EOL .
                'Database revision needs to be ' . $requiredRevision . ' but found ' . $revision
            );

            return false;
        }

        return true;
    }

    /**
     * Remove the timesheet lifecycle events subscriber, which would overwrite values for imported timesheet records.
     *
     * @param Connection $connection
     */
    protected function deactivateLifecycleCallbacks(Connection $connection)
    {
        $allListener = $connection->getEventManager()->getListeners();
        foreach ($allListener as $event => $listeners) {
            foreach ($listeners as $hash => $object) {
                if ($object instanceof TimesheetSubscriber) {
                    $connection->getEventManager()->removeEventListener([$event], $object);
                }
            }
        }
    }

    /**
     * Thanks to "xelozz -at- gmail.com", see http://php.net/manual/en/function.memory-get-usage.php#96280
     * @param int $size
     * @return string
     */
    protected function bytesHumanReadable($size)
    {
        $unit = ['b', 'kB', 'MB', 'GB'];
        $i = floor(log($size, 1024));
        $a = (int) $i;

        return @round($size / pow(1024, $i), 2) . ' ' . $unit[$a];
    }

    /**
     * @param string $table
     * @param array $where
     * @return array
     */
    protected function fetchAllFromImport($table, array $where = [])
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->connection->quoteIdentifier($this->dbPrefix . $table));

        foreach ($where as $column => $value) {
            $query->andWhere($query->expr()->eq($column, $value));
        }

        return $query->execute()->fetchAll();
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @param SymfonyStyle $io
     * @param object $object
     * @return bool
     */
    protected function validateImport(SymfonyStyle $io, $object)
    {
        $errors = $this->validator->validate($object);

        if ($errors->count() > 0) {
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $io->error(
                    (string) $error
                );
            }

            return false;
        }

        return true;
    }

    /**
     * -- are currently unsupported fields that can't be mapped
     *
     * ["userID"]=> string(9) "833336177"
     * ["name"]=> string(5) "admin"
     * ["alias"]=> NULL
     * --- ["status"]=> string(1) "0"
     * ["trash"]=> string(1) "0"
     * ["active"]=> string(1) "1"
     * ["mail"]=> string(21) "foo@bar.com"
     * ["password"]=> string(32) ""
     * ["passwordResetHash"]=> NULL
     * ["ban"]=> string(1) "0"
     * ["banTime"]=> string(1) "0"
     * --- ["secure"]=> string(30) ""
     * ["lastProject"]=> string(1) "2"
     * ["lastActivity"]=> string(1) "2"
     * ["lastRecord"]=> string(1) "2"
     * ["timeframeBegin"]=> string(10) "1304200800"
     * ["timeframeEnd"]=> string(1) "0"
     * ["apikey"]=> NULL
     * ["globalRoleID"]=> string(1) "1"
     *
     * @param SymfonyStyle $io
     * @param string $password
     * @param array $users
     * @param array $rates
     * @param string $timezone
     * @param string $language
     * @return int
     * @throws Exception
     */
    protected function importUsers(SymfonyStyle $io, $password, $users, $rates, $timezone, $language)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($users as $oldUser) {
            $isActive = (bool) $oldUser['active'] && !(bool) $oldUser['trash'] && !(bool) $oldUser['ban'];
            $role = (1 == $oldUser['globalRoleID']) ? User::ROLE_SUPER_ADMIN : User::DEFAULT_ROLE;

            $user = new User();
            $user->setUsername($oldUser['name'])
                ->setAlias($oldUser['alias'])
                ->setEmail($oldUser['mail'])
                ->setPlainPassword($password)
                ->setEnabled($isActive)
                ->setRoles([$role])
            ;

            $pwd = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($pwd);

            if (!$this->validateImport($io, $user)) {
                throw new Exception('Failed to validate user: ' . $user->getUsername());
            }

            // find and migrate user preferences
            $prefsToImport = ['ui.lang' => 'language', 'timezone' => 'timezone'];
            $preferences = $this->fetchAllFromImport('preferences', ['userID' => $oldUser['userID']]);
            foreach ($preferences as $pref) {
                $key = $pref['option'];

                if (!\array_key_exists($key, $prefsToImport)) {
                    continue;
                }

                if (empty($pref['value'])) {
                    continue;
                }

                $newPref = new UserPreference();
                $newPref
                    ->setName($prefsToImport[$key])
                    ->setValue($pref['value']);
                $user->addPreference($newPref);
            }

            // set default values if they were not set in the the user preferences
            $defaults = ['language' => $language, 'timezone' => $timezone];
            foreach ($defaults as $key => $default) {
                if (null === $user->getPreferenceValue($key)) {
                    $user->setPreferenceValue($key, $default);
                }
            }

            // find hourly rate
            foreach ($rates as $ratesRow) {
                if ($ratesRow['userID'] === $oldUser['userID'] && $ratesRow['activityID'] === null && $ratesRow['projectID'] === null) {
                    $newPref = new UserPreference();
                    $newPref
                        ->setName(UserPreference::HOURLY_RATE)
                        ->setValue($ratesRow['rate']);
                    $user->addPreference($newPref);
                }
            }

            try {
                $entityManager->persist($user);
                $entityManager->flush();
                if ($this->debug) {
                    $io->success('Created user: ' . $user->getUsername());
                }
                ++$counter;
            } catch (Exception $ex) {
                $io->error('Failed to create user: ' . $user->getUsername());
                $io->error('Reason: ' . $ex->getMessage());
            }

            $this->users[$oldUser['userID']] = $user;
        }

        return $counter;
    }

    /**
     * -- are currently unsupported fields that can't be mapped
     *
     * ["customerID"]=> string(2) "11"
     * ["name"]=> string(9) "Customer"
     * ["password"]=> NULL
     * ["passwordResetHash"]=> NULL
     * ["secure"]=> NULL
     * ["comment"]=> NULL
     * ["visible"]=> string(1) "1"
     * ["filter"]=> string(1) "0"
     * ["company"]=> string(14) "Customer Ltd."
     * --- ["vat"]=> string(2) "19"
     * ["contact"]=> string(2) "Someone"
     * ["street"]=> string(22) "Street name"
     * ["zipcode"]=> string(5) "12345"
     * ["city"]=> string(6) "Berlin"
     * ["phone"]=> NULL
     * ["fax"]=> NULL
     * ["mobile"]=> NULL
     * ["mail"]=> NULL
     * ["homepage"]=> NULL
     * ["trash"]=> string(1) "0"
     * ["timezone"]=> string(13) "Europe/Berlin"
     *
     * @param SymfonyStyle $io
     * @param array $customers
     * @param string $country
     * @param string $currency
     * @return int
     * @throws Exception
     */
    protected function importCustomers(SymfonyStyle $io, $customers, $country, $currency)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($customers as $oldCustomer) {
            $isActive = (bool) $oldCustomer['visible'] && !(bool) $oldCustomer['trash'];
            $name = $oldCustomer['name'];
            if (empty($name)) {
                $name = uniqid();
                $io->warning('Found empty customer name, setting it to: ' . $name);
            }

            $customer = new Customer();
            $customer
                ->setName($name)
                ->setComment($oldCustomer['comment'])
                ->setCompany($oldCustomer['company'])
                ->setFax($oldCustomer['fax'])
                ->setHomepage($oldCustomer['homepage'])
                ->setMobile($oldCustomer['mobile'])
                ->setEmail($oldCustomer['mail'])
                ->setPhone($oldCustomer['phone'])
                ->setContact($oldCustomer['contact'])
                ->setAddress($oldCustomer['street'] . PHP_EOL . $oldCustomer['zipcode'] . ' ' . $oldCustomer['city'])
                ->setTimezone($oldCustomer['timezone'])
                ->setVisible($isActive)
                ->setCountry(strtoupper($country))
                ->setCurrency(strtoupper($currency))
            ;

            $metaField = new CustomerMeta();
            $metaField->setName('_imported_id');
            $metaField->setValue($oldCustomer['customerID']);
            $metaField->setIsVisible(false);

            $customer->setMetaField($metaField);

            if (!$this->validateImport($io, $customer)) {
                throw new Exception('Failed to validate customer: ' . $customer->getName());
            }

            try {
                $entityManager->persist($customer);
                $entityManager->flush();
                if ($this->debug) {
                    $io->success('Created customer: ' . $customer->getName());
                }
                ++$counter;
            } catch (Exception $ex) {
                $io->error('Reason: ' . $ex->getMessage());
                $io->error('Failed to create customer: ' . $customer->getName());
            }

            $this->customers[$oldCustomer['customerID']] = $customer;
        }

        return $counter;
    }

    /**
     * -- are currently unsupported fields that can't be mapped
     *
     * ["projectID"]=> string(1) "1"
     * ["customerID"]=> string(1) "1"
     * ["name"]=> string(11) "Test"
     * ["comment"]=> string(0) ""
     * ["visible"]=> string(1) "1"
     * --- ["filter"]=> string(1) "0"
     * ["trash"]=> string(1) "1"
     * ["budget"]=> string(4) "0.00"
     * --- ["effort"]=> NULL
     * --- ["approved"]=> NULL
     * --- ["internal"]=> string(1) "0"
     *
     * @param SymfonyStyle $io
     * @param array $projects
     * @param array $fixedRates
     * @param array $rates
     * @return int
     * @throws Exception
     */
    protected function importProjects(SymfonyStyle $io, $projects, array $fixedRates, array $rates)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($projects as $oldProject) {
            $isActive = (bool) $oldProject['visible'] && !(bool) $oldProject['trash'];

            if (!isset($this->customers[$oldProject['customerID']])) {
                $io->error(
                    sprintf('Found project with unknown customer. Project ID: "%s", Name: "%s", Customer ID: "%s"', $oldProject['projectID'], $oldProject['name'], $oldProject['customerID'])
                );
                continue;
            }

            $customer = $this->customers[$oldProject['customerID']];
            $name = $oldProject['name'];
            if (empty($name)) {
                $name = uniqid();
                $io->warning('Found empty project name, setting it to: ' . $name);
            }

            $project = new Project();
            $project
                ->setCustomer($customer)
                ->setName($name)
                ->setComment($oldProject['comment'] ?: null)
                ->setVisible($isActive)
                ->setBudget($oldProject['budget'] ?: 0)
            ;

            $metaField = new ProjectMeta();
            $metaField->setName('_imported_id');
            $metaField->setValue($oldProject['projectID']);
            $metaField->setIsVisible(false);

            $project->setMetaField($metaField);

            if (!$this->validateImport($io, $project)) {
                throw new Exception('Failed to validate project: ' . $project->getName());
            }

            try {
                $entityManager->persist($project);
                if ($this->debug) {
                    $io->success('Created project: ' . $project->getName() . ' for customer: ' . $customer->getName());
                }
                ++$counter;
            } catch (Exception $ex) {
                $io->error('Failed to create project: ' . $project->getName());
                $io->error('Reason: ' . $ex->getMessage());
            }

            foreach ($fixedRates as $fixedRow) {
                // activity rates a re assigned in createActivity()
                if ($fixedRow['activityID'] !== null || $fixedRow['projectID'] === null) {
                    continue;
                }
                if ($fixedRow['projectID'] == $oldProject['projectID']) {
                    $projectRate = new ProjectRate();
                    $projectRate->setProject($project);
                    $projectRate->setRate($fixedRow['rate']);
                    $projectRate->setIsFixed(true);

                    try {
                        $entityManager->persist($projectRate);
                        if ($this->debug) {
                            $io->success('Created fixed project rate: ' . $project->getName() . ' for customer: ' . $customer->getName());
                        }
                    } catch (Exception $ex) {
                        $io->error(sprintf('Failed to create fixed project rate for %s: %s' . $project->getName(), $ex->getMessage()));
                    }
                }
            }

            foreach ($rates as $ratesRow) {
                if ($ratesRow['activityID'] !== null || $ratesRow['projectID'] === null) {
                    continue;
                }
                if ($ratesRow['projectID'] == $oldProject['projectID']) {
                    $projectRate = new ProjectRate();
                    $projectRate->setProject($project);
                    $projectRate->setRate($ratesRow['rate']);

                    if ($ratesRow['userID'] !== null) {
                        $projectRate->setUser($this->users[$ratesRow['userID']]);
                    }

                    try {
                        $entityManager->persist($projectRate);
                        if ($this->debug) {
                            $io->success('Created project rate: ' . $project->getName() . ' for customer: ' . $customer->getName());
                        }
                    } catch (Exception $ex) {
                        $io->error(sprintf('Failed to create project rate for %s: %s' . $project->getName(), $ex->getMessage()));
                    }
                }
            }

            $entityManager->flush();

            $this->projects[$oldProject['projectID']] = $project;
        }

        return $counter;
    }

    /**
     * -- are currently unsupported fields that can't be mapped
     *
     * $activities:
     * -- ["activityID"]=> string(1) "1"
     * ["name"]=> string(6) "Test"
     * ["comment"]=> string(0) ""
     * ["visible"]=> string(1) "1"
     * --- ["filter"]=> string(1) "0"
     * ["trash"]=> string(1) "1"
     *
     * $activityToProject
     * ["projectID"]=> string(1) "1"
     * ["activityID"]=> string(1) "1"
     * ["budget"]=> string(4) "0.00"
     * -- ["effort"]=> string(4) "0.00"
     * -- ["approved"]=> string(4) "0.00"
     *
     * @param SymfonyStyle $io
     * @param array $activities
     * @param array $activityToProject
     * @param array $fixedRates
     * @param array $rates
     * @return int
     * @throws Exception
     */
    protected function importActivities(SymfonyStyle $io, array $activities, array $activityToProject, array $fixedRates, array $rates)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        // remember which activity has at least one assigned project
        $oldActivityMapping = [];
        foreach ($activityToProject as $mapping) {
            $oldActivityMapping[$mapping['activityID']][] = $mapping['projectID'];
        }

        // create global activities
        foreach ($activities as $oldActivity) {
            $this->oldActivities[$oldActivity['activityID']] = $oldActivity;
            if (isset($oldActivityMapping[$oldActivity['activityID']])) {
                continue;
            }

            $this->createActivity($io, $entityManager, $oldActivity, $fixedRates, $rates, null);
            ++$counter;
        }

        $io->success('Created global activities: ' . $counter);

        // create project specific activities
        foreach ($activities as $oldActivity) {
            if (!isset($oldActivityMapping[$oldActivity['activityID']])) {
                continue;
            }
            foreach ($oldActivityMapping[$oldActivity['activityID']] as $projectId) {
                if (!isset($this->projects[$projectId])) {
                    throw new Exception(
                        'Invalid project linked to activity ' . $oldActivity['name'] . ': ' . $projectId
                    );
                }

                $this->createActivity($io, $entityManager, $oldActivity, $fixedRates, $rates, $projectId);
                ++$counter;
            }
        }

        return $counter;
    }

    /**
     * @param SymfonyStyle $io
     * @param ObjectManager $entityManager
     * @param array $oldActivity
     * @param array $fixedRates
     * @param array $rates
     * @param int|null $oldProjectId
     * @return Activity
     * @throws Exception
     */
    protected function createActivity(
        SymfonyStyle $io,
        ObjectManager $entityManager,
        array $oldActivity,
        array $fixedRates,
        array $rates,
        $oldProjectId = null
    ) {
        $oldActivityId = $oldActivity['activityID'];

        if (isset($this->activities[$oldActivityId][$oldProjectId])) {
            return $this->activities[$oldActivityId][$oldProjectId];
        }

        $isActive = (bool) $oldActivity['visible'] && !(bool) $oldActivity['trash'];
        $name = $oldActivity['name'];
        if (empty($name)) {
            $name = uniqid();
            $io->warning('Found empty activity name, setting it to: ' . $name);
        }

        if (null !== $oldProjectId && !isset($this->projects[$oldProjectId])) {
            throw new Exception(
                sprintf('Did not find project [%s], skipping activity creation [%s] %s', $oldProjectId, $oldActivityId, $name)
            );
        }

        $activity = new Activity();
        $activity
            ->setName($name)
            ->setComment($oldActivity['comment'] ?? null)
            ->setVisible($isActive)
            ->setBudget($oldActivity['budget'] ?? 0)
        ;

        if (null !== $oldProjectId) {
            $project = $this->projects[$oldProjectId];
            $activity->setProject($project);
        }

        $metaField = new ActivityMeta();
        $metaField->setName('_imported_id');
        $metaField->setValue($oldActivity['activityID']);
        $metaField->setIsVisible(false);

        $activity->setMetaField($metaField);

        if (!$this->validateImport($io, $activity)) {
            throw new Exception('Failed to validate activity: ' . $activity->getName());
        }

        try {
            $entityManager->persist($activity);
            if ($this->debug) {
                $io->success('Created activity: ' . $activity->getName());
            }
        } catch (Exception $ex) {
            $io->error('Failed to create activity: ' . $activity->getName());
            $io->error('Reason: ' . $ex->getMessage());
        }

        if (!isset($this->activities[$oldActivityId])) {
            $this->activities[$oldActivityId] = [];
        }
        $this->activities[$oldActivityId][$oldProjectId] = $activity;

        foreach ($fixedRates as $fixedRow) {
            if ($fixedRow['activityID'] === null) {
                continue;
            }
            if ($fixedRow['projectID'] !== null && $fixedRow['projectID'] !== $oldProjectId) {
                continue;
            }

            if ($fixedRow['activityID'] == $oldActivityId) {
                $activityRate = new ActivityRate();
                $activityRate->setActivity($activity);
                $activityRate->setRate($fixedRow['rate']);
                $activityRate->setIsFixed(true);

                try {
                    $entityManager->persist($activityRate);
                    if ($this->debug) {
                        $io->success('Created fixed activity rate: ' . $activity->getName());
                    }
                } catch (Exception $ex) {
                    $io->error(sprintf('Failed to create fixed activity rate for %s: %s' . $activity->getName(), $ex->getMessage()));
                }
            }
        }

        foreach ($rates as $ratesRow) {
            if ($ratesRow['activityID'] === null) {
                continue;
            }
            if ($ratesRow['projectID'] !== null && $ratesRow['projectID'] !== $oldProjectId) {
                continue;
            }

            if ($ratesRow['activityID'] == $oldActivityId) {
                $activityRate = new ActivityRate();
                $activityRate->setActivity($activity);
                $activityRate->setRate($ratesRow['rate']);

                if ($ratesRow['userID'] !== null) {
                    $activityRate->setUser($this->users[$ratesRow['userID']]);
                }

                try {
                    $entityManager->persist($activityRate);
                    if ($this->debug) {
                        $io->success('Created activity rate: ' . $activity->getName());
                    }
                } catch (Exception $ex) {
                    $io->error(sprintf('Failed to create activity rate for %s: %s' . $activity->getName(), $ex->getMessage()));
                }
            }
        }

        $entityManager->flush();

        return $activity;
    }

    /**
     * -- are currently unsupported fields that can't be mapped
     *
     * -- ["timeEntryID"]=> string(1) "1"
     * ["start"]=> string(10) "1306747800"
     * ["end"]=> string(10) "1306752300"
     * ["duration"]=> string(4) "4500"
     * ["userID"]=> string(9) "228899434"
     * ["projectID"]=> string(1) "1"
     * ["activityID"]=> string(1) "1"
     * ["description"]=> NULL
     * ["comment"]=> string(36) "a work description"
     * -- ["commentType"]=> string(1) "0"
     * ["cleared"]=> string(1) "0"
     * -- ["location"]=> string(0) ""
     * -- ["trackingNumber"]=> NULL
     * ["rate"]=> string(5) "50.00"
     * ["fixedRate"]=> string(4) "0.00"
     * -- ["budget"]=> NULL
     * -- ["approved"]=> NULL
     * -- ["statusID"]=> string(1) "1"
     * -- ["billable"]=> NULL
     *
     * @param SymfonyStyle $io
     * @param array $records
     * @param array $fixedRates
     * @param array $rates
     * @return int
     * @throws Exception
     */
    protected function importTimesheetRecords(SymfonyStyle $io, array $records, array $fixedRates, array $rates)
    {
        $errors = [
            'projectActivityMismatch' => [],
        ];
        $counter = 0;
        $failed = 0;
        $activityCounter = 0;
        $userCounter = 0;
        $entityManager = $this->getDoctrine()->getManager();
        $total = \count($records);

        $io->writeln('Importing timesheets, please wait');

        foreach ($records as $oldRecord) {
            $activity = null;
            $project = null;
            $activityId = $oldRecord['activityID'];
            $projectId = $oldRecord['projectID'];

            if (isset($this->projects[$projectId])) {
                $project = $this->projects[$projectId];
            } else {
                $io->error('Could not create timesheet record, missing project with ID: ' . $projectId);
                $failed++;
                continue;
            }

            $customerId = $project->getCustomer()->getId();

            if (isset($this->activities[$activityId][$projectId])) {
                $activity = $this->activities[$activityId][$projectId];
            } elseif (isset($this->activities[$activityId][null])) {
                $activity = $this->activities[$activityId][null];
            }

            if (null === $activity && isset($this->oldActivities[$activityId])) {
                $oldActivity = $this->oldActivities[$activityId];
                $activity = $this->createActivity($io, $entityManager, $oldActivity, $fixedRates, $rates, $projectId);
                ++$activityCounter;
            }

            // this should not happen at all
            if (null === $activity) {
                $io->error('Could not import timesheet record, missing activity with ID: ' . $activityId . '/' . $projectId . '/' . $customerId);
                $failed++;
                continue;
            }

            if (empty($oldRecord['end']) || $oldRecord['end'] === 0) {
                $io->error('Cannot import running timesheet record, skipping: ' . $oldRecord['timeEntryID']);
                $failed++;
                continue;
            }

            $duration = (int) ($oldRecord['end'] - $oldRecord['start']);

            // ----------------------- unknown user, damned missing data integrity in Kimai v1 -----------------------
            if (!isset($this->users[$oldRecord['userID']])) {
                $tempUserName = uniqid();
                $tempPassword = uniqid() . uniqid();

                $user = new User();
                $user->setUsername($tempUserName)
                    ->setAlias('Import: ' . $tempUserName)
                    ->setEmail($tempUserName . '@example.com')
                    ->setPlainPassword($tempPassword)
                    ->setEnabled(false)
                    ->setRoles([USER::ROLE_USER])
                ;

                $pwd = $this->encoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($pwd);

                if (!$this->validateImport($io, $user)) {
                    $io->error('Found timesheet record for unknown user and failed to create user, skipping timesheet: ' . $oldRecord['timeEntryID']);
                    $failed++;
                    continue;
                }

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                    if ($this->debug) {
                        $io->success('Created deactivated user: ' . $user->getUsername());
                    }
                    $userCounter++;
                } catch (Exception $ex) {
                    $io->error('Failed to create user: ' . $user->getUsername());
                    $io->error('Reason: ' . $ex->getMessage());
                    $failed++;
                    continue;
                }

                $this->users[$oldRecord['userID']] = $user;
            }
            // ----------------------- unknown user end -----------------------

            $timesheet = new Timesheet();

            $fixedRate = $oldRecord['fixedRate'];
            if (!empty($fixedRate) && 0.00 != $fixedRate) {
                $timesheet->setFixedRate($fixedRate);
            }

            $hourlyRate = $oldRecord['rate'];
            if (!empty($hourlyRate) && 0.00 != $hourlyRate) {
                $timesheet->setHourlyRate($hourlyRate);
            }

            if ($timesheet->getFixedRate() !== null) {
                $timesheet->setRate($timesheet->getFixedRate());
            } elseif ($timesheet->getHourlyRate() !== null) {
                $hourlyRate = (float) $timesheet->getHourlyRate();
                $rate = Util::calculateRate($hourlyRate, $duration);
                $timesheet->setRate($rate);
            }

            $user = $this->users[$oldRecord['userID']];
            $timezone = $user->getTimezone();
            $dateTimezone = new DateTimeZone('UTC');

            $begin = new DateTime('@' . $oldRecord['start']);
            $begin->setTimezone($dateTimezone);
            $end = new DateTime('@' . $oldRecord['end']);
            $end->setTimezone($dateTimezone);

            // ---------- workaround for localizeDates ----------
            // if getBegin() is not executed first, then the dates will we re-written in validateImport() below
            $timesheet->setBegin($begin)->setEnd($end)->getBegin();
            // --------------------------------------------------

            // ---------- this was a bug in the past, should not happen anymore ----------
            if ($activity->getProject() !== null && $project->getId() !== $activity->getProject()->getId()) {
                $errors['projectActivityMismatch'][] = $oldRecord['timeEntryID'];
                continue;
            }
            // ---------------------------------------------------------------------

            $timesheet
                ->setDescription($oldRecord['description'] ?? ($oldRecord['comment'] ?? null))
                ->setUser($this->users[$oldRecord['userID']])
                ->setBegin($begin)
                ->setEnd($end)
                ->setDuration($duration)
                ->setActivity($activity)
                ->setProject($project)
                ->setExported(\intval($oldRecord['cleared']) !== 0)
                ->setTimezone($timezone)
            ;

            if (!$this->validateImport($io, $timesheet)) {
                $io->caution('Failed to validate timesheet record: ' . $oldRecord['timeEntryID'] . ' - skipping!');
                $failed++;
                continue;
            }

            try {
                $entityManager->persist($timesheet);
                if ($this->debug) {
                    $io->success('Created timesheet record: ' . $timesheet->getId());
                }
                ++$counter;
            } catch (Exception $ex) {
                $io->error('Failed to create timesheet record: ' . $ex->getMessage());
                $failed++;
            }

            $io->write('.');
            if (0 == $counter % 80) {
                $entityManager->flush();
                $entityManager->clear(Timesheet::class);
                $io->writeln(' (' . $counter . '/' . $total . ')');
            }
        }

        $entityManager->flush();
        $entityManager->clear(Timesheet::class);

        for ($i = 0; $i < 80 - ($counter % 80); $i++) {
            $io->write(' ');
        }
        $io->writeln(' (' . $counter . '/' . $total . ')');

        if ($userCounter > 0) {
            $io->success('Created new users during timesheet import: ' . $userCounter);
        }
        if ($activityCounter > 0) {
            $io->success('Created new activities during timesheet import: ' . $activityCounter);
        }
        if (\count($errors['projectActivityMismatch']) > 0) {
            $io->error('Found invalid mapped project - activity combinations in these old timesheet recors: ' . implode(',', $errors['projectActivityMismatch']));
        }
        if ($failed > 0) {
            $io->error(sprintf('Failed importing %s timesheet records', $failed));
        }

        return $counter;
    }

    /** Imports Kimai v1 groups as teams and connects teams with users, customers and projects
     *
     * -- are currently unsupported fields that can't be mapped
     *
     * $groups
     * ["groupID"] => int(10) "1"
     * ["name"] => varchar(160) "a group name"
     * -- ["trash"] => tinyint(1) 1/0
     *
     * $groups_customers
     * ["groupID"] => int(10) "1"
     * ["customerID"] => int(10) "1"
     *
     * $groups_projects
     * ["groupID"] => int(10) "1"
     * ["projectID"] => int(10) "1"
     *
     * $groups_users
     * ["groupID"] => int(10) "1"
     * ["customerID"] => int(10) "1"
     * -- ["membershipRoleID"] => int(10) "1"
     *
     * @param SymfonyStyle $io
     * @param array $groups
     * @param array $groupToCustomer
     * @param array $groupToProject
     * @param array $groupToUser
     *
     * @return int
     * @throws Exception
     */
    protected function importGroups(SymfonyStyle $io, array $groups, array $groupToCustomer, array $groupToProject, array $groupToUser)
    {
        $counter = 0;
        $skippedTrashed = 0;
        $skippedEmpty = 0;
        $failed = 0;

        $newTeams = [];
        // create teams just with names of groups
        foreach ($groups as $group) {
            if ($group['trash'] === 1) {
                $io->warning(sprintf('Didn\'t import team: "%s" because it is trashed.', $group['name']));
                $skippedTrashed++;
                continue;
            }

            $team = new Team();
            $team->setName($group['name']);

            $newTeams[$group['groupID']] = $team;
        }

        // connect groups with users
        foreach ($groupToUser as $row) {
            if (!isset($newTeams[$row['groupID']])) {
                continue;
            }
            $team = $newTeams[$row['groupID']];

            if (!isset($this->users[$row['userID']])) {
                continue;
            }
            $user = $this->users[$row['userID']];

            $team->addUser($user);

            // first user in the team will become team lead
            if ($team->getTeamLead() == null) {
                $team->setTeamLead($user);
            }

            // any other user with admin role in the team will become team lead
            // should be the last added admin of the source group
            if ($row['membershipRoleID'] === 1) {
                $team->setTeamLead($user);
            }
        }

        // if team has no users it will not be persisted
        foreach ($newTeams as $oldId => $team) {
            if ($team->getTeamLead() === null) {
                $io->warning(sprintf('Didn\'t import team: %s because it has no users.', $team->getName()));
                ++$skippedEmpty;
                unset($newTeams[$oldId]);
            }
        }

        // connect groups with customers
        foreach ($groupToCustomer as $row) {
            if (!isset($newTeams[$row['groupID']])) {
                continue;
            }
            $team = $newTeams[$row['groupID']];

            if (!isset($this->customers[$row['customerID']])) {
                continue;
            }
            $customer = $this->customers[$row['customerID']];

            $team->addCustomer($customer);
        }

        // connect groups with projects
        foreach ($groupToProject as $row) {
            if (!isset($newTeams[$row['groupID']])) {
                continue;
            }
            $team = $newTeams[$row['groupID']];

            if (!isset($this->projects[$row['projectID']])) {
                continue;
            }
            $project = $this->projects[$row['projectID']];

            $team->addProject($project);

            if ($project->getCustomer() !== null) {
                $team->addCustomer($project->getCustomer());
            }
        }

        $entityManager = $this->getDoctrine()->getManager();

        // validate and persist each team
        foreach ($newTeams as $oldId => $team) {
            if (!$this->validateImport($io, $team)) {
                throw new Exception('Failed to validate team: ' . $team->getName());
            }

            try {
                $entityManager->persist($team);
                if ($this->debug) {
                    $io->success(
                        sprintf(
                            'Created team: %s with %s users, %s projects and %s customers.',
                            $team->getName(),
                            \count($team->getUsers()),
                            \count($team->getProjects()),
                            \count($team->getCustomers())
                        )
                    );
                }
                ++$counter;
                $this->teams[$oldId] = $team;
            } catch (Exception $ex) {
                $io->error('Failed to create team: ' . $team->getName());
                $io->error('Reason: ' . $ex->getMessage());
                ++$failed;
            }
        }

        $entityManager->flush();

        if ($skippedTrashed > 0) {
            $io->warning('Didn\'t import teams because they are trashed: ' . $skippedTrashed);
        }
        if ($skippedEmpty > 0) {
            $io->warning('Didn\'t import teams because they have no users: ' . $skippedEmpty);
        }
        if ($failed > 0) {
            $io->error('Failed importing teams: ' . $failed);
        }

        return $counter;
    }
}
