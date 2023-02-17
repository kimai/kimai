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
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Timesheet\Util;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
 * This command is way to (messy and) complex to be tested, so excuse me but I use something which I normally don't like:
 * @codeCoverageIgnore
 */
final class KimaiImporterCommand extends Command
{
    // minimum required Kimai and database version, lower versions are not supported by this command
    public const MIN_VERSION = '1.0.1';
    public const MIN_REVISION = '1388';

    public const BATCH_SIZE = 200;

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
     * Global across all instances.
     *
     * @var User[]
     */
    private $users = [];
    /**
     * Instance specific mappings of user IDs to cache IDs
     *
     * @var string[]
     */
    private $userIds = [];
    /**
     * Old TeamID => new Team()
     * Global across all instances.
     *
     * @var Team[]
     */
    private $teams = [];
    /**
     * Instance specific mappings of team IDs to cache IDs
     *
     * @var string[]
     */
    private $teamIds = [];
    /**
     * Global across all instances.
     *
     * @var Customer[]
     */
    private $customers = [];
    /**
     * Old Project ID => new Project()
     * Global across all instances.
     *
     * @var Project[]
     */
    private $projects = [];
    /**
     * id => [projectId => Activity]
     * @var array<Activity[]>
     */
    private $activities = [];
    /**
     * @var bool
     */
    private $debug = false;
    /**
     * Global activities (either because they were global OR becuase --global was used).
     *
     * @var array
     */
    private $oldActivities = [];

    private $options = [];

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
            ->setName('kimai:import:v1')
            ->setAliases(['kimai:import-v1'])
            ->setDescription('Import data from a Kimai v1 installation')
            ->setHelp('This command allows you to import the most important data from a Kimi v1 installation.')
            ->addArgument(
                'connection',
                InputArgument::REQUIRED,
                'The database connection as URL, e.g.: mysql://user:password@127.0.0.1:3306/kimai?charset=utf8'
            )
            ->addArgument('password', InputArgument::REQUIRED, 'The new password for all imported user')
            ->addOption('country', null, InputOption::VALUE_OPTIONAL, 'The default country for customer (2-character uppercase)', 'DE')
            ->addOption('currency', null, InputOption::VALUE_OPTIONAL, 'The default currency for customer (code like EUR, CHF, GBP or USD)', 'EUR')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The database prefix(es) for your old Kimai v1 instances', ['kimai_'])
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'Default timezone for imported users', date_default_timezone_get())
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'Default language for imported users', User::DEFAULT_LANGUAGE)
            ->addOption('global', null, InputOption::VALUE_NONE, 'If set, activities without mapping will be created globally instead of project-specific (default behavior)')
            ->addOption('fix-utf8', null, InputOption::VALUE_NONE, 'Trying to fix some known encoding problems (wrong encoded character: äÄüÜöÖß)). Use with caution!')
            ->addOption('fix-email', null, InputOption::VALUE_REQUIRED, 'Domain that is used to fix empty email addresses (will be set to username@domain)')
            ->addOption('fix-timesheet', null, InputOption::VALUE_NONE, 'Fix known timesheet problems (negative durations)')
            ->addOption('skip-error-rates', null, InputOption::VALUE_NONE, 'Ignores rate mappings for unknown users (known bug in Kimai 1 when deleting users)')
            ->addOption('merge-customer', null, InputOption::VALUE_NONE, 'Merges the customers from multiple instances by their ID (only works with: multiple --prefix)')
            ->addOption('merge-project', null, InputOption::VALUE_NONE, 'Merges the projects from multiple instances by their ID (only works with: multiple --prefix)')
            ->addOption('merge-user', null, InputOption::VALUE_NONE, 'Merges the users from multiple instances (only works with: multiple --prefix and same username/email combinations)')
            ->addOption('merge-team', null, InputOption::VALUE_NONE, 'Merges the team from multiple instances (only works with: multiple --prefix and same team names)')
            ->addOption('create-team', null, InputOption::VALUE_NONE, 'Creates a new team for every instance (using the --prefix name as team name)')
            ->addOption('alias-as-account-number', null, InputOption::VALUE_NONE, 'Creates a new team for every instance (using the --prefix name as team name)')
            ->addOption('meta-comment', null, InputOption::VALUE_REQUIRED, 'Name of the meta field which will be used to store the comment field')
            ->addOption('meta-location', null, InputOption::VALUE_REQUIRED, 'Name of the meta field which will be used to store the location field')
            ->addOption('meta-tracking-number', null, InputOption::VALUE_REQUIRED, 'Name of the meta field which will be used to store the trackingNumber field')
            ->addOption('skip-team-customers', null, InputOption::VALUE_NONE, 'If given, the team (group) permissions for customers will not be synced')
            ->addOption('skip-team-projects', null, InputOption::VALUE_NONE, 'If given, the team (group) permissions for projects will not be synced')
            ->addOption('skip-team-activities', null, InputOption::VALUE_NONE, 'If given, the team (group) permissions for activities will not be synced')
        ;
    }

    private function prepareOptionsFromInput(InputInterface $input): array
    {
        return [
            'url' => $input->getArgument('connection'),
            'password' => $input->getArgument('password'),
            'unknownAsGlobal' => $input->getOption('global'),
            'country' => $input->getOption('country'),
            'currency' => $input->getOption('currency'),
            'prefix' => $input->getOption('prefix'),
            'language' => $input->getOption('language'),
            'timezone' => $input->getOption('timezone'),
            'skip-error-rates' => $input->getOption('skip-error-rates'),
            'fix-email' => $input->getOption('fix-email'),
            'fix-utf8' => $input->getOption('fix-utf8'),
            'fix-timesheet' => $input->getOption('fix-timesheet'),
            'merge-customer' => $input->getOption('merge-customer'),
            'merge-project' => $input->getOption('merge-project'),
            'merge-user' => $input->getOption('merge-user'),
            'merge-team' => $input->getOption('merge-team'),
            'merge-activity' => false,
            'instance-team' => $input->getOption('create-team'),
            'alias-as-account-number' => $input->getOption('create-team'),
            'meta-comment' => $input->getOption('meta-comment'),
            'meta-location' => $input->getOption('meta-location'),
            'meta-trackingNumber' => $input->getOption('meta-tracking-number'),
            'skip-team-customers' => $input->getOption('skip-team-customers'),
            'skip-team-projects' => $input->getOption('skip-team-projects'),
            'skip-team-activities' => $input->getOption('skip-team-activities'),
        ];
    }

    private function validateOptions(array $options, SymfonyStyle $io): bool
    {
        $password = $options['password'];
        if (null === $password || \strlen($password = trim($password)) < 8) {
            $io->error('Password length is not sufficient, at least 8 character are required');

            return false;
        }

        $country = $options['country'];
        if (null === $country || 2 != \strlen($country = trim($country))) {
            $io->error('Country code needs to be exactly 2 character');

            return false;
        }

        $currency = $options['currency'];
        if (null === $currency || 3 != \strlen($currency = trim($currency))) {
            $io->error('Currency code needs to be exactly 3 character');

            return false;
        }

        if (!\is_array($options['prefix'])) {
            $io->error('Prefix must be an array');

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $options = $this->prepareOptionsFromInput($input);
        if (!$this->validateOptions($options, $io)) {
            $io->error('Invalid importer configuration, exiting');

            return 1;
        }

        $this->options = $options;

        // do not convert the times to UTC, Kimai 1 stored them already in UTC
        Type::overrideType(Types::DATETIME_MUTABLE, DateTimeType::class);
        // don't calculate rates ... this was done in Kimai 1
        $this->deactivateLifecycleCallbacks();
        // create reading database connection to Kimai 1
        $this->connection = $connection = DriverManager::getConnection(['url' => $options['url']]);

        $this->connection->getConfiguration()->setSQLLogger(null);
        $this->doctrine->getConnection()->getConfiguration()->setSQLLogger(null);

        foreach ($options['prefix'] as $prefix) {
            $this->dbPrefix = $prefix;
            if (!$this->checkDatabaseVersion($connection, $io, self::MIN_VERSION, self::MIN_REVISION)) {
                return 1;
            }
        }

        $bytesStart = memory_get_usage(true);
        $timeStart = time();
        $allImports = 0;

        foreach ($options['prefix'] as $prefix) {
            $this->dbPrefix = $prefix;

            $this->teamIds = [];
            $this->userIds = [];
            $this->oldActivities = [];

            if (!$options['merge-customer']) {
                $this->customers = [];
            }

            if (!$options['merge-project']) {
                $this->projects = [];
            }

            if (!$options['merge-user']) {
                $this->users = [];
            }

            if (!$options['merge-team']) {
                $this->teams = [];
            }

            if (!$options['merge-activity']) {
                $this->activities = [];
            }

            $io->title(sprintf('Handling data from table prefix: %s', $this->dbPrefix));

            if ($options['fix-email'] !== null) {
                $io->text('Fixing email addresses');
                $this->fixEmail($options['fix-email']);
            }

            if ($options['fix-utf8']) {
                $io->text('Fixing encoding issues now');
                $this->fixEncoding();
            }

            if ($options['fix-timesheet']) {
                $io->text('Fixing timesheet issues now');
                $this->fixTimesheet();
            }

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

            $io->success('Fetched Kimai v1 data, validating now');
            $validationMessages = $this->validateKimai1Data($options, $users, $customer, $projects, $activities, $rates);
            if (!empty($validationMessages)) {
                foreach ($validationMessages as $errorMessage) {
                    $io->error($errorMessage);
                }

                return 1;
            }
            $io->success('Pre-validated data, importing now');

            try {
                $counter = $this->importUsers($io, $options['password'], $users, $rates, $options['timezone'], $options['language']);
                $allImports += $counter;
                $io->success('Imported users: ' . $counter);
            } catch (Exception $ex) {
                $io->error('Failed to import users: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }

            try {
                $counter = $this->importCustomers($io, $customer, $options['country'], $options['currency'], $options['timezone']);
                $allImports += $counter;
                unset($customer);
                $io->success('Imported customers: ' . $counter);
            } catch (Exception $ex) {
                $io->error('Failed to import customers: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }

            try {
                $counter = $this->importProjects($io, $projects, $fixedRates, $rates);
                $allImports += $counter;
                unset($projects);
                $io->success('Imported projects: ' . $counter);
            } catch (Exception $ex) {
                $io->error('Failed to import projects: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }

            try {
                $counter = $this->importActivities($io, $activities, $fixedRates, $rates);
                $allImports += $counter;
            } catch (Exception $ex) {
                $io->error('Failed to import activities: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }

            try {
                $counter = $this->importGroups($io);
                $allImports += $counter;
                $io->success('Imported groups/teams: ' . $counter);
            } catch (Exception $ex) {
                $io->error('Failed to import groups/teams: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }

            try {
                if ($options['instance-team'] && \count($users) > 0) {
                    $this->createInstanceTeam($io, $users, $activities, $prefix);
                }
            } catch (\Exception $ex) {
                $io->error('Failed to create instance team: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }

            try {
                $counter = $this->importTimesheetRecords($output, $io, $fixedRates, $rates);
                $allImports += $counter;
                unset($fixedRates);
                unset($rates);
                $io->success('Imported timesheet records: ' . $counter);
            } catch (Exception $ex) {
                $io->error('Failed to import timesheet records: ' . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

                return 1;
            }
        }

        $bytesImported = memory_get_usage(true);
        $timeEnd = time();

        $io->success(
            'Runtime (seconds): ' . ($timeEnd - $timeStart) . PHP_EOL .
            'Imported entries: ' . $allImports . PHP_EOL .
            'Memory at start: ' . $this->bytesHumanReadable($bytesStart) . PHP_EOL .
            'Memory after import: ' . $this->bytesHumanReadable($bytesImported) . PHP_EOL .
            'Total memory usage: ' . $this->bytesHumanReadable($bytesImported - $bytesStart)
        );

        return 0;
    }

    private function validateKimai1Data(array $options, array $users, array $customer, array $projects, array $activities, array $rates): array
    {
        $validationMessages = [];

        try {
            $usedEmails = [];
            $userIds = [];
            foreach ($users as $oldUser) {
                $userIds[] = $oldUser['userID'];
                if (empty($oldUser['mail'])) {
                    $validationMessages[] = sprintf(
                        'User "%s" with ID %s has no email',
                        $oldUser['name'],
                        $oldUser['userID']
                    );
                    continue;
                }
                if (\in_array($oldUser['mail'], $usedEmails)) {
                    $validationMessages[] = sprintf(
                        'Email "%s" for user "%s" with ID %s is already used',
                        $oldUser['mail'],
                        $oldUser['name'],
                        $oldUser['userID']
                    );
                }
                if ($this->options['alias-as-account-number'] && mb_strlen($oldUser['alias']) > 30) {
                    $validationMessages[] = sprintf(
                        'Alias "%s" for user "%s" with ID %s, which should be used as account number, is longer than 30 character',
                        $oldUser['alias'],
                        $oldUser['name'],
                        $oldUser['userID']
                    );
                }
                $usedEmails[] = $oldUser['mail'];
            }

            $customerIds = [];
            foreach ($customer as $oldCustomer) {
                $customerIds[] = $oldCustomer['customerID'];
                if (($customerNameLength = mb_strlen($oldCustomer['name'])) > 150) {
                    $validationMessages[] = sprintf(
                        'Customer name "%s" (ID %s) is too long. Max. 150 character are allowed, found %s.',
                        $oldCustomer['name'],
                        $oldCustomer['customerID'],
                        $customerNameLength
                    );
                }
            }

            foreach ($projects as $oldProject) {
                if (!\in_array($oldProject['customerID'], $customerIds)) {
                    $validationMessages[] = sprintf(
                        'Project "%s" with ID %s has unknown customer with ID %s',
                        $oldProject['name'],
                        $oldProject['projectID'],
                        $oldProject['customerID']
                    );
                }
                if (($projectNameLength = mb_strlen($oldProject['name'])) > 150) {
                    $validationMessages[] = sprintf(
                        'Project name "%s" (ID %s) is too long. Max. 150 character are allowed, found %s.',
                        $oldProject['name'],
                        $oldProject['projectID'],
                        $projectNameLength
                    );
                }
            }

            foreach ($activities as $oldActivity) {
                if (($activityNameLength = mb_strlen($oldActivity['name'])) > 150) {
                    $validationMessages[] = sprintf(
                        'Activity name "%s" (ID %s) is too long. Max. 150 character are allowed, found %s.',
                        $oldActivity['name'],
                        $oldActivity['activityID'],
                        $activityNameLength
                    );
                }
            }

            if (!$options['skip-error-rates']) {
                foreach ($rates as $oldRate) {
                    if ($oldRate['userID'] === null) {
                        continue;
                    }
                    if (!\in_array($oldRate['userID'], $userIds)) {
                        $validationMessages[] = sprintf(
                            'Unknown user with ID "%s" found for rate with project "%s" and activity "%s"',
                            $oldRate['userID'],
                            $oldRate['projectID'],
                            $oldRate['activityID']
                        );
                    }
                }
            }
        } catch (Exception $ex) {
            $validationMessages[] = $ex->getMessage();
        }

        return $validationMessages;
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
    protected function checkDatabaseVersion(Connection $connection, SymfonyStyle $io, string $requiredVersion, string $requiredRevision): bool
    {
        $optionColumn = $connection->quoteIdentifier('option');
        $qb = $connection->createQueryBuilder();

        try {
            $connection->createQueryBuilder()
                ->select('1')
                ->from($connection->quoteIdentifier($this->dbPrefix . 'configuration'))
                ->execute();
        } catch (Exception $e) {
            $io->error(
                sprintf('Cannot read from table "%sconfiguration", make sure that your prefix "%s" is correct.', $this->dbPrefix, $this->dbPrefix)
            );

            return false;
        }

        $version = $connection->createQueryBuilder()
            ->select('value')
            ->from($connection->quoteIdentifier($this->dbPrefix . 'configuration'))
            ->where($qb->expr()->eq($optionColumn, ':option'))
            ->setParameter('option', 'version')
            ->execute()
            ->fetchOne();

        if (1 == version_compare($requiredVersion, $version)) {
            $io->error(
                'Import can only performed from an up-to-date Kimai version:' . PHP_EOL .
                'Needs at least ' . $requiredVersion . ' but found ' . $version
            );

            return false;
        }

        $revision = $connection->createQueryBuilder()
            ->select('value')
            ->from($connection->quoteIdentifier($this->dbPrefix . 'configuration'))
            ->where($qb->expr()->eq($optionColumn, ':option'))
            ->setParameter('option', 'revision')
            ->execute()
            ->fetchOne();

        if (1 == version_compare($requiredRevision, $revision)) {
            $io->error(
                'Import can only performed from an up-to-date Kimai version:' . PHP_EOL .
                'Database revision needs to be ' . $requiredRevision . ' but found ' . $revision
            );

            return false;
        }

        $requiredTables = [
            'preferences',
            'users',
            'customers',
            'projects',
            'activities',
            'projects_activities',
            'timeSheet',
            'fixedRates',
            'rates',
            'groups',
            'groups_customers',
            'groups_projects',
            'groups_users',
            'groups_activities',
        ];

        $tables = [];
        foreach ($requiredTables as $table) {
            $tables[] = $this->dbPrefix . $table;
        }

        if (!$connection->getSchemaManager()->tablesExist($tables)) {
            $io->error(
                'Import cannot be started, missing tables. Required are: ' . implode(', ', $tables)
            );

            return false;
        }

        return true;
    }

    /**
     * Remove the timesheet lifecycle events subscriber, which would overwrite values for imported timesheet records.
     */
    protected function deactivateLifecycleCallbacks()
    {
        $connection = $connection = $this->getDoctrine()->getConnection();

        $allListener = $connection->getEventManager()->getListeners();
        foreach ($allListener as $event => $listeners) {
            foreach ($listeners as $hash => $object) {
                if ($object instanceof TimesheetSubscriber) {
                    $connection->getEventManager()->removeEventListener([$event], $object);
                /* @phpstan-ignore-next-line  */
                } elseif ($object instanceof \KimaiPlugin\AuditTrailBundle\Doctrine\MetadataSubscriber) {
                    // deactivate audit plugin listener
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
    protected function bytesHumanReadable(int $size): string
    {
        if ($size === 0) {
            return '0';
        }

        $unit = ['b', 'kB', 'MB', 'GB'];
        $i = floor(log($size, 1024));
        $a = (int) $i;

        return @round($size / pow(1024, $i), 2) . ' ' . $unit[$a];
    }

    protected function fetchAllFromImport(string $table, array $where = []): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->connection->quoteIdentifier($this->dbPrefix . $table));

        foreach ($where as $column => $value) {
            $query->andWhere($query->expr()->eq($column, $value));
        }

        return $query->execute()->fetchAll();
    }

    protected function countFromImport(string $table, array $where = []): int
    {
        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->connection->quoteIdentifier($this->dbPrefix . $table));

        foreach ($where as $column => $value) {
            $query->andWhere($query->expr()->eq($column, $value));
        }

        return $query->execute()->fetchOne();
    }

    protected function fetchIteratorFromImport(string $table): \Traversable
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->connection->quoteIdentifier($this->dbPrefix . $table));

        return $query->execute()->iterateAssociative();
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

    private function getCachedUser(int $id): ?User
    {
        if (isset($this->userIds[$id])) {
            $id = $this->userIds[$id];
        }

        if (isset($this->users[$id])) {
            return $this->users[$id];
        }

        return null;
    }

    private function isKnownUser(SymfonyStyle $io, array $oldUser): bool
    {
        $cacheId = $oldUser['userID'];

        if (isset($this->userIds[$cacheId])) {
            return true;
        }

        // workaround when importing multiple instances at once: search if the user exists by unique values
        foreach ($this->users as $tmpUserId => $tmpUser) {
            $newEmail = strtolower($tmpUser->getEmail());
            $newName = strtolower($tmpUser->getUsername());
            $oldEmail = strtolower($oldUser['mail']);
            $oldName = strtolower($oldUser['name']);
            if ($newEmail !== $oldEmail && $newName !== $oldName) {
                continue;
            }
            if ($newEmail === $oldEmail && $newName !== $oldName) {
                $io->warning(sprintf(
                    'Found problematic user combination. Username matches, but email does not. Cached user: ID %s, %s, %s. New user: ID %s, %s, %s.',
                    $tmpUser->getId(),
                    $newEmail,
                    $newName,
                    $oldUser['userID'],
                    $oldEmail,
                    $oldName
                ));
            }
            if ($newEmail !== $oldEmail && $newName === $oldName) {
                $io->warning(sprintf(
                    'Found problematic user combination. Emails matches, but username does not. Cached user: ID %s, %s, %s. New user: ID %s, %s, %s.',
                    $tmpUser->getId(),
                    $newEmail,
                    $newName,
                    $oldUser['userID'],
                    $oldEmail,
                    $oldName
                ));
            }
            if ($newEmail === $oldEmail && $newName === $oldName) {
                if (isset($this->userIds[$cacheId])) {
                    throw new Exception('Cannot import duplicate user ' . $newName . ' as the ID is already cached');
                }

                $this->userIds[$cacheId] = $tmpUserId;

                return true;
            }
        }

        return false;
    }

    private function setUserCache(array $oldUser, User $user): void
    {
        $this->users[$oldUser['userID']] = $user;
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
    protected function importUsers(SymfonyStyle $io, string $password, array $users, array $rates, string $timezone, string $language): int
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($users as $oldUser) {
            if ($this->isKnownUser($io, $oldUser)) {
                continue;
            }

            $isActive = (bool) $oldUser['active'] && !(bool) $oldUser['trash'] && !(bool) $oldUser['ban'];
            $role = (1 == $oldUser['globalRoleID']) ? User::ROLE_SUPER_ADMIN : User::DEFAULT_ROLE;

            $user = new User();
            $user->setUsername($oldUser['name'])
                ->setEmail($oldUser['mail'])
                ->setPlainPassword($password)
                ->setEnabled($isActive)
                ->setRoles([$role])
            ;

            if ($this->options['alias-as-account-number']) {
                $user->setAccountNumber(mb_substr($oldUser['alias'], 0, 30));
            } else {
                $user->setAlias($oldUser['alias']);
            }

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

            // set default values if they were not set in the user preferences
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
                    $newPref->setName(UserPreference::HOURLY_RATE);
                    $newPref->setValue($ratesRow['rate']);
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

            $this->setUserCache($oldUser, $user);
        }

        return $counter;
    }

    private function getCachedCustomer(int $id): ?Customer
    {
        if (isset($this->customers[$id])) {
            return $this->customers[$id];
        }

        return null;
    }

    private function isKnownCustomer(array $oldCustomer): bool
    {
        $cacheId = $oldCustomer['customerID'];

        return isset($this->customers[$cacheId]);
    }

    private function setCustomerCache(array $oldCustomer, Customer $customer): void
    {
        $this->customers[$oldCustomer['customerID']] = $customer;
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
     * @param string $timezone
     * @return int
     * @throws Exception
     */
    protected function importCustomers(SymfonyStyle $io, $customers, $country, $currency, string $timezone)
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($customers as $oldCustomer) {
            if ($this->isKnownCustomer($oldCustomer)) {
                continue;
            }

            $isActive = (bool) $oldCustomer['visible'] && !(bool) $oldCustomer['trash'];
            $name = $oldCustomer['name'];
            if (empty($name)) {
                $name = uniqid();
                $io->warning('Found empty customer name, setting it to: ' . $name);
            }

            $newTimezone = $oldCustomer['timezone'];
            if (empty($newTimezone)) {
                $newTimezone = $timezone;
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
                ->setTimezone($newTimezone)
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

            $this->setCustomerCache($oldCustomer, $customer);
        }

        return $counter;
    }

    private function getCachedProject(int $id): ?Project
    {
        if (isset($this->projects[$id])) {
            return $this->projects[$id];
        }

        return null;
    }

    private function isKnownProject(array $oldProject): bool
    {
        $cacheId = $oldProject['projectID'];

        return isset($this->projects[$cacheId]);
    }

    private function setProjectCache(array $oldProject, Project $project): void
    {
        $this->projects[$oldProject['projectID']] = $project;
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
    protected function importProjects(SymfonyStyle $io, $projects, array $fixedRates, array $rates): int
    {
        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($projects as $oldProject) {
            if ($this->isKnownProject($oldProject)) {
                continue;
            }

            $isActive = (bool) $oldProject['visible'] && !(bool) $oldProject['trash'];

            $customer = $this->getCachedCustomer($oldProject['customerID']);
            if ($customer === null) {
                $io->error(
                    sprintf('Found project with unknown customer. Project ID: "%s", Name: "%s", Customer ID: "%s"', $oldProject['projectID'], $oldProject['name'], $oldProject['customerID'])
                );
                continue;
            }

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
                        $projectRate->setUser($this->getCachedUser($ratesRow['userID']));
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

            $this->setProjectCache($oldProject, $project);
        }

        return $counter;
    }

    private function getCachedActivity(int $id, ?int $projectId = null): ?Activity
    {
        if (isset($this->activities[$id][$projectId])) {
            return $this->activities[$id][$projectId];
        }

        return null;
    }

    private function isKnownActivity(array $oldActivity, ?int $projectId = null): bool
    {
        $cacheId = $oldActivity['activityID'];

        if (isset($this->activities[$cacheId][$projectId])) {
            return true;
        }

        return false;
    }

    private function setActivityCache(array $oldActivity, Activity $activity, ?int $projectId = null): void
    {
        $cacheId = $oldActivity['activityID'];

        if (!isset($this->activities[$cacheId])) {
            $this->activities[$cacheId] = [];
        }
        $this->activities[$cacheId][$projectId] = $activity;
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
     * @param array $fixedRates
     * @param array $rates
     * @return int
     * @throws Exception
     */
    protected function importActivities(SymfonyStyle $io, array $activities, array $fixedRates, array $rates): int
    {
        $activityToProject = $this->fetchAllFromImport('projects_activities');

        $counter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        // remember which activity has at least one assigned project
        $oldActivityMapping = [];
        if ($this->options['unknownAsGlobal']) {
            $oldActivityMapping['___GLOBAL___'][] = PHP_INT_MAX;
        } else {
            foreach ($activityToProject as $mapping) {
                $oldActivityMapping[$mapping['activityID']][] = $mapping['projectID'];
            }
        }

        $global = 0;
        $project = 0;

        // create global activities
        foreach ($activities as $oldActivity) {
            $this->oldActivities[$oldActivity['activityID']] = $oldActivity;
            if (isset($oldActivityMapping[$oldActivity['activityID']])) {
                continue;
            }

            $this->createActivity($io, $entityManager, $oldActivity, $fixedRates, $rates, null);
            ++$counter;
            ++$global;
        }

        if ($global > 0) {
            $io->success('Created global activities: ' . $counter);
        }

        // create project specific activities
        foreach ($activities as $oldActivity) {
            if (!isset($oldActivityMapping[$oldActivity['activityID']])) {
                continue;
            }
            foreach ($oldActivityMapping[$oldActivity['activityID']] as $projectId) {
                $this->createActivity($io, $entityManager, $oldActivity, $fixedRates, $rates, $projectId);
                ++$counter;
                ++$project;
            }
        }

        if ($project > 0) {
            $io->success('Created project specific activities: ' . $project);
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

        if ($this->isKnownActivity($oldActivity, $oldProjectId)) {
            return $this->getCachedActivity($oldActivityId, $oldProjectId);
        }

        $isActive = (bool) $oldActivity['visible'] && !(bool) $oldActivity['trash'];
        $name = $oldActivity['name'];
        if (empty($name)) {
            $name = uniqid();
            $io->warning('Found empty activity name, setting it to: ' . $name);
        }

        $activity = new Activity();
        $activity->setName($name);
        $activity->setComment($oldActivity['comment'] ?? null);
        $activity->setVisible($isActive);
        $activity->setBudget($oldActivity['budget'] ?? 0);

        if (null !== $oldProjectId) {
            $project = $this->getCachedProject($oldProjectId);
            if ($project === null) {
                throw new Exception(
                    sprintf(
                        'Did not find project [%s], skipping activity creation [%s] %s',
                        $oldProjectId,
                        $oldActivityId,
                        $name
                    )
                );
            }
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

        $this->setActivityCache($oldActivity, $activity, $oldProjectId);

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
                    $activityRate->setUser($this->getCachedUser($ratesRow['userID']));
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
     * @param array $fixedRates
     * @param array $rates
     * @return int
     * @throws Exception
     */
    protected function importTimesheetRecords(OutputInterface $output, SymfonyStyle $io, array $fixedRates, array $rates): int
    {
        $records = $this->fetchIteratorFromImport('timeSheet');
        $total = $this->countFromImport('timeSheet');

        $errors = [
            'projectActivityMismatch' => [],
        ];
        $counter = 0;
        $failed = 0;
        $activityCounter = 0;
        $userCounter = 0;
        $entityManager = $this->getDoctrine()->getManager();

        $io->writeln('Importing timesheets, please wait');
        $io->writeln('');

        $progressBar = new ProgressBar($output, $total);

        foreach ($records as $oldRecord) {
            $activity = null;
            $activityId = $oldRecord['activityID'];
            $projectId = $oldRecord['projectID'];
            $project = $this->getCachedProject($projectId);

            if ($project === null) {
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

            if (empty($oldRecord['end'])) {
                $io->error('Cannot import running timesheet record, skipping: ' . $oldRecord['timeEntryID']);
                $failed++;
                continue;
            }

            $duration = (int) ($oldRecord['end'] - $oldRecord['start']);

            // ----------------------- unknown user, damned missing data integrity in Kimai v1 -----------------------
            if ($this->getCachedUser($oldRecord['userID']) === null) {
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

                $this->setUserCache($oldRecord, $user);
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

            $user = $this->getCachedUser($oldRecord['userID']);
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
                ->setUser($user)
                ->setBegin($begin)
                ->setEnd($end)
                ->setDuration($duration)
                ->setActivity($activity)
                ->setProject($project)
                ->setExported(\intval($oldRecord['cleared']) !== 0)
                ->setTimezone($user->getTimezone())
            ;

            if ($this->options['meta-comment'] !== null) {
                $timesheet->setDescription($oldRecord['description']);

                if ($oldRecord['comment'] !== null && $oldRecord['comment'] !== '') {
                    $meta = new TimesheetMeta();
                    $meta->setName($this->options['meta-comment']);
                    $meta->setValue($oldRecord['comment']);
                    $meta->setIsVisible(true);
                    $timesheet->setMetaField($meta);
                }
            }

            if ($this->options['meta-location'] !== null && $oldRecord['location'] !== null && $oldRecord['location'] !== '') {
                $meta = new TimesheetMeta();
                $meta->setName($this->options['meta-location']);
                $meta->setValue($oldRecord['location']);
                $meta->setIsVisible(true);
                $timesheet->setMetaField($meta);
            }

            if ($this->options['meta-trackingNumber'] !== null && $oldRecord['trackingNumber'] !== null && $oldRecord['trackingNumber'] !== '') {
                $meta = new TimesheetMeta();
                $meta->setName($this->options['meta-trackingNumber']);
                $meta->setValue($oldRecord['trackingNumber']);
                $meta->setIsVisible(true);
                $timesheet->setMetaField($meta);
            }

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

            $progressBar->advance();
            if (0 === $counter % self::BATCH_SIZE) {
                $entityManager->flush();
                $entityManager->clear(TimesheetMeta::class);
                $entityManager->clear(Timesheet::class);
            }
        }

        $entityManager->flush();
        $entityManager->clear(TimesheetMeta::class);
        $entityManager->clear(Timesheet::class);

        $progressBar->finish();
        $io->writeln('');

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

    private function getCachedGroup(int $id): ?Team
    {
        if (isset($this->teamIds[$id])) {
            $id = $this->teamIds[$id];
        }

        if (isset($this->teams[$id])) {
            return $this->teams[$id];
        }

        return null;
    }

    private function isKnownGroup(array $oldGroup): bool
    {
        $cacheId = $oldGroup['groupID'];

        if (isset($this->teamIds[$cacheId])) {
            return true;
        }

        // workaround when importing multiple instances at once: search if the group/team exists by unique values
        foreach ($this->teams as $tmpTeamId => $tmpTeam) {
            if ($tmpTeam->getName() === $oldGroup['name']) {
                if (isset($this->teamIds[$cacheId])) {
                    throw new Exception('Cannot import duplicate group "' . $tmpTeam->getName() . '" as the ID is already cached');
                }

                $this->teamIds[$cacheId] = $tmpTeamId;

                return true;
            }
        }

        return false;
    }

    private function setGroupCache(array $oldGroup, Team $team): void
    {
        $this->teams[$oldGroup['groupID']] = $team;
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
     * @return int
     * @throws Exception
     */
    protected function importGroups(SymfonyStyle $io): int
    {
        $groups = $this->fetchAllFromImport('groups');
        $groupToUser = $this->fetchAllFromImport('groups_users');
        $groupToCustomer = [];
        $groupToProject = [];
        $groupToActivity = [];

        if (!$this->options['skip-team-customers']) {
            $groupToCustomer = $this->fetchAllFromImport('groups_customers');
        }
        if (!$this->options['skip-team-projects']) {
            $groupToProject = $this->fetchAllFromImport('groups_projects');
        }
        if (!$this->options['skip-team-activities']) {
            $groupToActivity = $this->fetchAllFromImport('groups_activities');
        }

        $counter = 0;
        $skippedTrashed = 0;
        $skippedEmpty = 0;
        $failed = 0;

        $newTeams = [];
        // create teams just with names of groups
        foreach ($groups as $group) {
            if ($group['trash'] === 1) {
                $io->warning(sprintf('Skipping team "%s" because it is trashed.', $group['name']));
                $skippedTrashed++;
                continue;
            }

            if (!$this->isKnownGroup($group)) {
                $team = new Team();
                $team->setName($group['name']);
            } else {
                $team = $this->getCachedGroup($group['groupID']);
            }

            $this->setGroupCache($group, $team);
            $newTeams[$group['groupID']] = $team;
        }

        // connect groups with users
        foreach ($groupToUser as $row) {
            if (!isset($newTeams[$row['groupID']])) {
                continue;
            }
            $team = $newTeams[$row['groupID']];

            $user = $this->getCachedUser($row['userID']);
            if ($user === null) {
                continue;
            }

            $team->addUser($user);

            // first user in the team will become team lead
            if (!$team->hasTeamleads()) {
                $team->addTeamlead($user);
            }

            // any other user with admin role in the team will become team lead
            // should be the last added admin of the source group
            if ($row['membershipRoleID'] === 1) {
                $team->addTeamlead($user);
            }
        }

        // if team has no users it will not be persisted
        foreach ($newTeams as $oldId => $team) {
            if (!$team->hasUsers()) {
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

            $customer = $this->getCachedCustomer($row['customerID']);
            if ($customer === null) {
                continue;
            }

            $team->addCustomer($customer);
        }

        // connect groups with projects
        foreach ($groupToProject as $row) {
            if (!isset($newTeams[$row['groupID']])) {
                continue;
            }
            $team = $newTeams[$row['groupID']];

            $project = $this->getCachedProject($row['projectID']);
            if ($project === null) {
                continue;
            }

            $team->addProject($project);

            if ($project->getCustomer() !== null) {
                $team->addCustomer($project->getCustomer());
            }
        }

        // connect groups with activities
        foreach ($groupToActivity as $row) {
            if (!isset($newTeams[$row['groupID']])) {
                continue;
            }
            $team = $newTeams[$row['groupID']];

            $activity = $this->getCachedActivity($row['activityID']);
            if ($activity === null) {
                continue;
            }

            $team->addActivity($activity);

            $activityProject = $activity->getProject();
            if ($activityProject !== null) {
                $team->addProject($activityProject);
                $team->addCustomer($activityProject->getCustomer());
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
            } catch (Exception $ex) {
                $io->error('Failed to create team: ' . $team->getName());
                $io->error('Reason: ' . $ex->getMessage());
                ++$failed;
            }
        }

        $entityManager->flush();

        if ($skippedTrashed > 0) {
            $io->warning('Skipped teams because they are trashed: ' . $skippedTrashed);
        }
        if ($skippedEmpty > 0) {
            $io->warning('Skipped teams because they have no users: ' . $skippedEmpty);
        }
        if ($failed > 0) {
            $io->error('Failed importing teams: ' . $failed);
        }

        return $counter;
    }

    private function createInstanceTeam(SymfonyStyle $io, array $users, array $activities, string $name): void
    {
        $team = new Team();
        $team->setName($name);
        $teamlead = $users[array_key_first($users)];
        $teamlead = $this->getCachedUser($teamlead['userID']);
        $team->addTeamlead($teamlead);
        foreach ($users as $oldUser) {
            $team->addUser($this->getCachedUser($oldUser['userID']));
            foreach ($activities as $oldActivity) {
                $activity = $this->getCachedActivity($oldActivity['activityID'], null);
                if ($activity !== null) {
                    $team->addActivity($activity);
                }
            }
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($team);
        $io->success('Created instance team: ' . $team->getName());
        $entityManager->flush();
    }

    private function fixEmail(string $domain): void
    {
        $query = $this->connection->createQueryBuilder()
            ->update($this->dbPrefix . 'users')
            ->set('mail', sprintf('CONCAT(LOWER(name), "_import@%s")', $domain))
            ->where("mail = '' OR mail IS null")
        ;
        $query->execute();
    }

    private function fixTimesheet(): void
    {
        $query = $this->connection->createQueryBuilder()
            ->update($this->dbPrefix . 'timeSheet')
            ->set('end', 'start')
            ->set('duration', '0')
            ->set('rate', '0')
            ->where('start > end')
        ;
        $query->execute();
    }

    private function fixEncoding(): void
    {
        // https://onlineasciitools.com/convert-ascii-to-utf8
        $searchReplace = [
            'Ã¤' => 'ä',
            'Ã„' => 'Ä',
            'Ã¼' => 'ü',
            'Ãœ' => 'Ü',
            'Ã¶' => 'ö',
            'Ã–' => 'Ö',
            'ÃŸ' => 'ß',
            'â¦' => '-',
        ];

        $tablesColumns = [
            'timeSheet' => ['comment', 'description', 'location', 'trackingNumber'],
            'users' => ['name', 'alias'],
            'activities' => ['name', 'comment'],
            'projects' => ['name', 'comment'],
            'customers' => ['name', 'comment'],
            'groups' => ['name'],
            'statuses' => ['status'],
            'expenses' => ['designation', 'comment'],
        ];

        foreach ($tablesColumns as $table => $columns) {
            foreach ($columns as $column) {
                foreach ($searchReplace as $search => $replace) {
                    $query = $this->connection->createQueryBuilder()
                        ->update($this->dbPrefix . $table, $this->dbPrefix . $table)
                        ->set($column, sprintf('REPLACE(%s, "%s", "%s")', $column, $search, $replace))
                        ->where($column . ' LIKE "%' . $search . '%"')
                    ;
                    $query->execute();
                }
            }
        }
    }
}
