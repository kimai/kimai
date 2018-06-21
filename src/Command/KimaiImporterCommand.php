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
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command used to import data from a Kimai v1 installation.
 * Getting help in improving this script would be fantastic, it currently only handles the most basic use-cases.
 */
class KimaiImporterCommand extends Command
{
    // minimum required Kimai and database version, lower versions are not supported by this command
    const MIN_VERSION = '1.0.1';
    const MIN_REVISION = '1388';

    /**
     * Create the user default passwords
     * @var UserPasswordEncoder
     */
    protected $encoder;
    /**
     * Validates the entities before they will be created
     * @var ValidatorInterface
     */
    protected $validator;
    /**
     * Connection to the Kimai v2 database to write imported data to
     * @var RegistryInterface
     */
    protected $doctrine;
    /**
     * Connection to the old database to import data from
     * @var Connection
     */
    protected $connection;
    /**
     * Prefix for the v1 database tables.
     * @var string
     */
    protected $dbPrefix = '';
    /**
     * @var User[]
     */
    protected $users = [];
    /**
     * @var Customer[]
     */
    protected $customers = [];
    /**
     * @var Project[]
     */
    protected $projects = [];
    /**
     * id => [projectId => Activity]
     * @var Activity[]
     */
    protected $activities = [];
    /**
     * activityId => activity[]
     * @var array
     */
    protected $unassignedActivities = [];
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @param UserPasswordEncoderInterface $encoder
     * @param RegistryInterface $registry
     * @param ValidatorInterface $validator
     */
    public function __construct(
        UserPasswordEncoderInterface $encoder,
        RegistryInterface $registry,
        ValidatorInterface $validator
    ) {
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
                'The database connection as URL, e.g.: mysql://user:password@127.0.0.1:3306/kimai?charset=latin1'
            )
            ->addArgument('prefix', InputArgument::REQUIRED, 'The database prefix for the old Kimai v1 tables')
            ->addArgument('password', InputArgument::REQUIRED, 'The new password for all imported user')
            ->addArgument('country', InputArgument::OPTIONAL, 'The default country for customer', 'de')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deactivateLifecycleCallbacks($this->getDoctrine()->getConnection());

        $io = new SymfonyStyle($input, $output);

        $config = new Configuration();
        $connectionParams = ['url' => $input->getArgument('connection')];
        $this->connection = DriverManager::getConnection($connectionParams, $config);

        $this->dbPrefix = $input->getArgument('prefix');

        $password = $input->getArgument('password');
        if (trim(strlen($password)) < 6) {
            $io->error('Password length is not sufficient, at least 6 character are required');

            return;
        }

        $country = $input->getArgument('country');
        if (trim(strlen($country)) != 2) {
            $io->error('Country length needs to be exactly 2 character');

            return;
        }

        if (!$this->checkDatabaseVersion($io, self::MIN_VERSION, self::MIN_REVISION)) {
            return;
        }

        // pre-load all data to make sure we can fully import everything
        $users = null;
        $customer = null;
        $projects = null;
        $activities = null;
        $records = null;
        $activityToProject = null;

        $bytesStart = memory_get_usage(true);

        try {
            $users = $this->fetchAllFromImport('users');
        } catch (\Exception $ex) {
            $io->error('Failed to load users: ' . $ex->getMessage());

            return;
        }

        try {
            $customer = $this->fetchAllFromImport('customers');
        } catch (\Exception $ex) {
            $io->error('Failed to load customers: ' . $ex->getMessage());

            return;
        }

        try {
            $projects = $this->fetchAllFromImport('projects');
        } catch (\Exception $ex) {
            $io->error('Failed to load projects: ' . $ex->getMessage());

            return;
        }

        try {
            $activities = $this->fetchAllFromImport('activities');
        } catch (\Exception $ex) {
            $io->error('Failed to load activities: ' . $ex->getMessage());

            return;
        }

        try {
            $activityToProject = $this->fetchAllFromImport('projects_activities');
        } catch (\Exception $ex) {
            $io->error('Failed to load activities-project mapping: ' . $ex->getMessage());

            return;
        }

        try {
            $records = $this->fetchAllFromImport('timeSheet');
        } catch (\Exception $ex) {
            $io->error('Failed to load timeSheet: ' . $ex->getMessage());

            return;
        }

        $bytesCached = memory_get_usage(true);

        $io->success('Fetched Kimai v1 data, trying to import now ...');

        $allImports = 0;

        try {
            $counter = $this->importUsers($io, $password, $users);
            $allImports += $counter;
            $io->success('Imported users: ' . $counter);
        } catch (\Exception $ex) {
            $io->error('Failed to import users: ' . $ex->getMessage());

            return;
        }

        try {
            $counter = $this->importCustomers($io, $customer, $country);
            $allImports += $counter;
            $io->success('Imported customers: ' . $counter);
        } catch (\Exception $ex) {
            $io->error('Failed to import customers: ' . $ex->getMessage());

            return;
        }

        try {
            $counter = $this->importProjects($io, $projects);
            $allImports += $counter;
            $io->success('Imported projects: ' . $counter);
        } catch (\Exception $ex) {
            $io->error('Failed to import projects: ' . $ex->getMessage());

            return;
        }

        try {
            $counter = $this->importActivities($io, $activities, $activityToProject);
            $allImports += $counter;
            $io->success('Imported activities: ' . $counter);
        } catch (\Exception $ex) {
            $io->error('Failed to import activities: ' . $ex->getMessage());

            return;
        }

        try {
            $counter = $this->importTimesheetRecords($io, $records);
            $allImports += $counter;
            $io->success('Imported timesheet records: ' . $counter);
        } catch (\Exception $ex) {
            $io->error('Failed to import timesheet records: ' . $ex->getMessage());

            return;
        }

        // TODO support fixedRates (projectID, activityID, rate)
        // TODO support rates (userID, projectID, activityID, rate)
        // TODO dump yaml config from configuration (adminmail, currency_name, date_format_0, language, roundPrecision)
        // TODO support preferences (ui.lang, timezone)
        // TODO support expenses - new database required

        $bytesImported = memory_get_usage(true);

        $io->success(
            'Memory usage: ' . PHP_EOL .
            'Start: ' . $this->bytesHumanReadable($bytesStart) . PHP_EOL .
            'After caching: ' . $this->bytesHumanReadable($bytesCached) . PHP_EOL .
            'After import: ' . $this->bytesHumanReadable($bytesImported) . PHP_EOL .
            'Total consumption for importing ' . $allImports . ' new database entries: ' .
            $this->bytesHumanReadable($bytesImported - $bytesStart)
        );
    }

    /**
     * Checks if the ghiven database connection for import has an underlying database with a compatible structure.
     * This is checked againstto the Kimai version and database revision.
     *
     * @param SymfonyStyle $io
     * @param $requiredVersion
     * @param $requiredRevision
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function checkDatabaseVersion(SymfonyStyle $io, $requiredVersion, $requiredRevision)
    {
        $versionQuery = 'SELECT `value` from ' . $this->dbPrefix . 'configuration WHERE `option` = "version"';
        $revisionQuery = 'SELECT `value` from ' . $this->dbPrefix . 'configuration WHERE `option` = "revision"';

        $version = $this->getImportConnection()->query($versionQuery)->fetchColumn();
        $revision = $this->getImportConnection()->query($revisionQuery)->fetchColumn();

        if (version_compare($requiredVersion, $version) == 1) {
            $io->error(
                'Import can only performed from an up-to-date Kimai version:' . PHP_EOL .
                'Needs at least ' . $requiredVersion . ' but found ' . $version
            );

            return false;
        }

        if (version_compare($requiredRevision, $revision) == 1) {
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
        foreach ($allListener as $name => $listener) {
            if (in_array($name, ['prePersist', 'preUpdate'])) {
                foreach ($listener as $service => $class) {
                    if ($class === TimesheetSubscriber::class) {
                        $connection->getEventManager()->removeEventListener(['prePersist', 'preUpdate'], $class);
                    }
                }
            }
        }
    }

    /**
     * Thanks to "xelozz -at- gmail.com", see http://php.net/manual/en/function.memory-get-usage.php#96280
     * @param $size
     * @return string
     */
    protected function bytesHumanReadable($size)
    {
        $unit = ['b', 'kB', 'MB', 'GB'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * @param $table
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fetchAllFromImport($table)
    {
        return $this->getImportConnection()->query('SELECT * from ' . $this->dbPrefix . $table)->fetchAll();
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getImportConnection()
    {
        return $this->connection;
    }

    /**
     * @return RegistryInterface
     */
    protected function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @param SymfonyStyle $io
     * @param $object
     * @return bool
     */
    protected function validateImport(SymfonyStyle $io, $object)
    {
        $errors = $this->validator->validate($object);

        if ($errors->count() > 0) {
            /** @var \Symfony\Component\Validator\ConstraintViolation $error */
            foreach ($errors as $error) {
                $value = $error->getInvalidValue();
                $io->error(
                    $error->getPropertyPath()
                    . " (" . (is_array($value) ? implode(',', $value) : $value) . ")"
                    . "\n    "
                    . $error->getMessage()
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
     * @return int
     * @throws \Exception
     */
    protected function importUsers(SymfonyStyle $io, $password, $users)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($users as $oldUser) {
            $isActive = (bool) $oldUser['active'] && !(bool) $oldUser['trash'] && !(bool) $oldUser['ban'];
            $role = ($oldUser['globalRoleID'] == 1) ? User::ROLE_SUPER_ADMIN : User::DEFAULT_ROLE;

            $user = new User();
            $user->setUsername($oldUser['name'])
                ->setAlias($oldUser['alias'])
                ->setEmail($oldUser['mail'])
                ->setPlainPassword($password)
                ->setActive($isActive)
                ->setRoles([$role])
            ;

            $pwd = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($pwd);

            if (!$this->validateImport($io, $user)) {
                throw new \Exception('Failed to validate user: ' . $user->getUsername());
            }

            try {
                $entityManager->persist($user);
                $entityManager->flush();
                if ($this->debug) {
                    $io->success('Created user: ' . $user->getUsername());
                }
                ++$counter;
            } catch (\Exception $ex) {
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
     * --- ["mail"]=> NULL
     * --- ["homepage"]=> NULL
     * ["trash"]=> string(1) "0"
     * ["timezone"]=> string(13) "Europe/Berlin"
     *
     * @param SymfonyStyle $io
     * @param array $customers
     * @param string $country
     * @return int
     * @throws \Exception
     */
    protected function importCustomers(SymfonyStyle $io, $customers, $country)
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
                ->setMobile($oldCustomer['mobile'])
                ->setPhone($oldCustomer['phone'])
                ->setContact($oldCustomer['contact'])
                ->setAddress($oldCustomer['street'] . PHP_EOL . $oldCustomer['zipcode'] . ' ' . $oldCustomer['city'])
                ->setTimezone($oldCustomer['timezone'])
                ->setVisible($isActive)
                ->setCountry($country)
            ;

            if (!$this->validateImport($io, $customer)) {
                throw new \Exception('Failed to validate customer: ' . $customer->getName());
            }

            try {
                $entityManager->persist($customer);
                $entityManager->flush();
                if ($this->debug) {
                    $io->success('Created customer: ' . $customer->getName());
                }
                ++$counter;
            } catch (\Exception $ex) {
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
     * --- ["budget"]=> string(4) "0.00"
     * --- ["effort"]=> NULL
     * --- ["approved"]=> NULL
     * --- ["internal"]=> string(1) "0"
     *
     * @param SymfonyStyle $io
     * @param array $projects
     * @return int
     * @throws \Exception
     */
    protected function importProjects(SymfonyStyle $io, $projects)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($projects as $oldProject) {
            $isActive = (bool) $oldProject['visible'] && !(bool) $oldProject['trash'];
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
            ;

            if (!$this->validateImport($io, $project)) {
                throw new \Exception('Failed to validate project: ' . $project->getName());
            }

            try {
                $entityManager->persist($project);
                $entityManager->flush();
                if ($this->debug) {
                    $io->success('Created project: ' . $project->getName() . ' for customer: ' . $customer->getName());
                }
                ++$counter;
            } catch (\Exception $ex) {
                $io->error('Failed to create project: ' . $project->getName());
                $io->error('Reason: ' . $ex->getMessage());
            }

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
     * -- ["budget"]=> string(4) "0.00"
     * -- ["effort"]=> string(4) "0.00"
     * -- ["approved"]=> string(4) "0.00"
     *
     * @param SymfonyStyle $io
     * @param array $activities
     * @param array $activityToProject
     * @return int
     * @throws \Exception
     */
    protected function importActivities(SymfonyStyle $io, array $activities, array $activityToProject)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();
        $oldActivityMapping = [];
        foreach ($activityToProject as $mapping) {
            $oldActivityMapping[$mapping['activityID']] = $mapping['projectID'];
        }

        foreach ($activities as $oldActivity) {
            if (isset($oldActivityMapping[$oldActivity['activityID']])) {
                $projectId = $oldActivityMapping[$oldActivity['activityID']];
                $project = null;

                if (!isset($this->projects[$projectId])) {
                    throw new \Exception(
                        'Invalid project linked to activity ' . $oldActivity['name'] . ': ' . $projectId
                    );
                }

                $project = $this->projects[$projectId];

                $this->unassignedActivities[$oldActivity['activityID']] = $oldActivity;
                $this->createActivity($io, $entityManager, $project, $oldActivity);
                ++$counter;
            } else {
                $this->unassignedActivities[$oldActivity['activityID']] = $oldActivity;
            }
        }

        return $counter;
    }

    /**
     * @param SymfonyStyle $io
     * @param ObjectManager $entityManager
     * @param Project $project
     * @param array $oldActivity
     * @return Activity
     * @throws \Exception
     */
    protected function createActivity(
        SymfonyStyle $io,
        ObjectManager $entityManager,
        Project $project,
        array $oldActivity
    ) {
        $activityId = $oldActivity['activityID'];
        if (isset($this->activities[$activityId][$project->getId()])) {
            return $this->activities[$activityId][$project->getId()];
        }

        $isActive = (bool) $oldActivity['visible'] && !(bool) $oldActivity['trash'];
        $name = $oldActivity['name'];
        if (empty($name)) {
            $name = uniqid();
            $io->warning('Found empty activity name, setting it to: ' . $name);
        }

        $activity = new Activity();
        $activity
            ->setName($name)
            ->setComment($oldActivity['comment'] ?: null)
            ->setVisible($isActive)
            ->setProject($project)
        ;

        if (!$this->validateImport($io, $activity)) {
            throw new \Exception('Failed to validate activity: ' . $activity->getName());
        }

        try {
            $entityManager->persist($activity);
            $entityManager->flush();
            if ($this->debug) {
                $io->success('Created activity: ' . $activity->getName());
            }
        } catch (\Exception $ex) {
            $io->error('Failed to create activity: ' . $activity->getName());
            $io->error('Reason: ' . $ex->getMessage());
        }

        if (!isset($this->activities[$activityId])) {
            $this->activities[$activityId] = [];
        }
        $this->activities[$activityId][$project->getId()] = $activity;

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
     * -- ["cleared"]=> string(1) "0"
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
     * @return int
     * @throws \Exception
     */
    protected function importTimesheetRecords(SymfonyStyle $io, array $records)
    {
        $counter = 0;
        $activityCounter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($records as $oldRecord) {
            $activity = null;
            $project = null;
            $activityId = $oldRecord['activityID'];
            $projectId = $oldRecord['projectID'];

            if (isset($this->projects[$projectId])) {
                $project = $this->projects[$projectId];
            } else {
                $io->error('Could not create timesheet record, missing project with ID: ' . $projectId);
                continue;
            }

            if (isset($this->activities[$activityId][$projectId])) {
                $activity = $this->activities[$activityId][$projectId];
            }

            if ($activity === null && isset($this->unassignedActivities[$activityId])) {
                $oldActivity = $this->unassignedActivities[$activityId];
                $activity = $this->createActivity($io, $entityManager, $project, $oldActivity);
                ++$activityCounter;
            }

            if ($activity === null) {
                $io->error('Could not create timesheet record, missing activity with ID: ' . $activityId);
                continue;
            }

            $duration = $oldRecord['end'] - $oldRecord['start'];

            $rate = $oldRecord['fixedRate'];
            if ((empty($rate) || $rate == 0.00) && !empty($oldRecord['rate'])) {
                $hourlyRate = (float) $oldRecord['rate'];
                $rate = (float) $hourlyRate * ($duration / 3600);
                $rate = round($rate, 2);
            }

            $timesheet = new Timesheet();
            $timesheet
                ->setDescription($oldRecord['description'] ?: ($oldRecord['comment'] ?: null))
                ->setUser($this->users[$oldRecord['userID']])
                ->setBegin(new \DateTime("@" . $oldRecord['start']))
                ->setEnd(new \DateTime("@" . $oldRecord['end']))
                ->setDuration($duration)
                ->setActivity($activity)
                ->setRate($rate)
            ;

            if (!$this->validateImport($io, $timesheet)) {
                throw new \Exception('Failed to validate timesheet record: ' . $timesheet->getId());
            }

            try {
                $entityManager->persist($timesheet);
                $entityManager->flush();
                if ($this->debug) {
                    $io->success('Created timesheet record: ' . $timesheet->getId());
                }
                ++$counter;
            } catch (\Exception $ex) {
                $io->error('Failed to create timesheet record: ' . $timesheet->getId());
                $io->error('Reason: ' . $ex->getMessage());
            }

            if ($counter % 500 == 0) {
                $io->writeln('Imported ' . $counter . ' timesheet records, import ongoing ...');
            }
        }

        if ($activityCounter > 0) {
            $io->success('Created new (previously unattached) activities during timesheet import: ' . $activityCounter);
        }

        return $counter;
    }
}
