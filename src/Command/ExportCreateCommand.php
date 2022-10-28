<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Export\ServiceExport;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ExportQuery;
use App\Repository\Query\TimesheetQuery;
use App\Timesheet\DateTimeFactory;
use App\Utils\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportCreateCommand extends Command
{
    /**
     * @var string|null
     */
    private $directory;
    /**
     * @var array<string>
     */
    private $emails = [];

    private $serviceExport;
    private $customerRepository;
    private $projectRepository;
    private $translator;

    public function __construct(
        ServiceExport $serviceExport,
        CustomerRepository $customerRepository,
        ProjectRepository $projectRepository,
        Translator $translator
    ) {
        $this->serviceExport = $serviceExport;
        $this->customerRepository = $customerRepository;
        $this->projectRepository = $projectRepository;
        $this->translator = $translator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:export:create')
            ->setDescription('Create exports')
            ->setHelp('Create exports by several different filters and sent them via email.')

            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start date (format: 2020-01-01, default: start of the month)', null)
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End date (format: 2020-01-31, default: end of the month)', null)
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'Timezone for start and end date query (fallback: server timezone)', null)
            ->addOption('customer', null, InputOption::VALUE_REQUIRED, 'A specific customer by ID', null)
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale to use', 'en')
            ->addOption('project', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of project IDs (these projects MUST belong to the given customer)', null)
            ->addOption('set-exported', null, InputOption::VALUE_NONE, 'Whether the included items should be marked as exported (default: false)')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Export template', null)
            ->addOption('exported', null, InputOption::VALUE_OPTIONAL, 'Exported filter for export entries. By default only "not exported" items are fetched (possible values: exported, all)', null)
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'Absolute path for the rendered export documents (either this or "email" needs to be set)', null)
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'List of recipient comma separated email addresses (either this or "directory" needs to b e set)', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $exportedFilter = TimesheetQuery::STATE_NOT_EXPORTED;
        switch ($input->getOption('exported')) {
            case null:
                break;

            case 'all':
                $exportedFilter = TimesheetQuery::STATE_ALL;
                break;

            case 'exported':
                $exportedFilter = TimesheetQuery::STATE_EXPORTED;
                break;

            default:
                $io->error('Unknown "exported" filter given');

                return 1;
        }

        $locale = $input->getOption('locale');
        if ($locale === null) {
            $io->error('You need to set a "locale"');

            return 1;
        }
        \Locale::setDefault($locale);
        $this->translator->setLocale($locale);

        $timezone = $input->getOption('timezone');
        if ($timezone === null) {
            $timezone = date_default_timezone_get();
        }

        $timezone = new \DateTimeZone($timezone);
        $dateFactory = new DateTimeFactory($timezone);

        $customerID = $input->getOption('customer');
        if (empty($customerID)) {
            $io->error('You need to set a "customer" ID');

            return 1;
        }

        $customer = $this->customerRepository->find($customerID);
        if ($customer === null) {
            $io->error('Invalid "customer" ID given');

            return 1;
        }

        $projectIDs = $input->getOption('project');
        $projects = [];
        if (!empty($projectIDs)) {
            $projectIDs = explode(',', $projectIDs);
            $projects = $this->projectRepository->findByIds($projectIDs);
            foreach ($projects as $project) {
                if ($project->getCustomer() !== $customer) {
                    $io->error(sprintf(
                        'Invalid "project" ID %s given, linked customer is not matching.',
                        $project->getId()
                    ));

                    return 1;
                }
            }
        }

        $template = $input->getOption('template');
        if ($template === null) {
            $io->error('You must pass the "template" option');

            return 1;
        }
        $renderer = $this->serviceExport->getRendererById($template);
        if ($renderer === null) {
            $io->error('Unknown export "template", available are:');
            $rows = [];
            foreach ($this->serviceExport->getRenderer() as $renderer) {
                $rows[] = [$renderer->getId()];
            }
            $io->table(['ID'], $rows);

            return 1;
        }

        $start = $input->getOption('start');
        if (!empty($start)) {
            try {
                $start = $dateFactory->createDateTime($start);
            } catch (\Exception $ex) {
                $io->error('Invalid start date given');

                return 1;
            }
        }
        if (!$start instanceof \DateTime) {
            $start = $dateFactory->getStartOfMonth();
        }
        $start->setTime(0, 0, 0);

        $end = $input->getOption('end');
        if (!empty($end)) {
            try {
                $end = $dateFactory->createDateTime($end);
            } catch (\Exception $ex) {
                $io->error('Invalid end date given');

                return 1;
            }
        }

        if (empty($end)) {
            $end = $dateFactory->getEndOfMonth($start);
        }

        if (!$end instanceof \DateTime) {
            $end = $dateFactory->getEndOfMonth();
        }

        $end->setTime(23, 59, 59);

        $this->directory = rtrim(sys_get_temp_dir(), '/') . '/';
        if ($input->getOption('directory') !== null) {
            $this->directory = rtrim($input->getOption('directory'), '/') . '/';
        }

        if (!is_dir($this->directory) || !is_writable($this->directory)) {
            $io->error('Invalid "directory" given: ' . $this->directory);

            return 1;
        }

        if ($input->getOption('email') !== null) {
            $emails = explode(',', $input->getOption('email'));
            foreach ($emails as $email) {
                if (!empty($email)) {
                    $this->emails[] = $email;
                }
            }
        }

        if (\count($this->emails) === 0 && $this->directory === null) {
            $io->error('You must set one of "directory" or "email" parameter');

            return 1;
        }

        $markAsExported = false;
        if ($input->getOption('set-exported')) {
            $markAsExported = true;
        }

        // =============== VALIDATION END ===============

        $query = new ExportQuery();
        $query->setCustomers([$customer]);
        $query->setProjects($projects);
        $query->setBegin($start);
        $query->setEnd($end);
        $query->setExported($exportedFilter);
        //$query->setRenderer($template);
        //$query->setMarkAsExported($markAsExported);

        $io = new SymfonyStyle($input, $output);

        $entries = $this->serviceExport->getExportItems($query);
        if (\count($entries) === 0) {
            $io->success('No entries found, skipping');

            return 0;
        }

        $response = $renderer->render($entries, $query);
        $file = $this->savePreview($response);

        if ($markAsExported) {
            $this->serviceExport->setExported($entries);
        }

        $io->success('Saved export to: ' . $file);

        return 0;
    }

    private function savePreview(Response $response): string
    {
        $filename = uniqid('invoice_');
        $directory = rtrim($this->directory, '/') . '/';

        if ($response->headers->has('Content-Disposition')) {
            $disposition = $response->headers->get('Content-Disposition');
            $parts = explode(';', $disposition);
            foreach ($parts as $part) {
                if (stripos($part, 'filename=') === false) {
                    continue;
                }
                $filename = explode('filename=', $part);
                if (\count($filename) > 1) {
                    $filename = $filename[1];
                }
            }
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();
            $file->move($directory, $filename);
        } else {
            (new Filesystem())->dumpFile($directory . $filename, $response->getContent());
        }

        return $directory . $filename;
    }
}
