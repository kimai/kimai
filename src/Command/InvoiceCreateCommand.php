<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Invoice\ServiceInvoice;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\InvoiceQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\UserRepository;
use App\Timesheet\DateTimeFactory;
use App\Utils\SearchTerm;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(name: 'kimai:invoice:create')]
final class InvoiceCreateCommand extends Command
{
    private ?string $previewDirectory = null;
    private bool $previewUniqueFile = false;

    public function __construct(
        private ServiceInvoice $serviceInvoice,
        private CustomerRepository $customerRepository,
        private ProjectRepository $projectRepository,
        private InvoiceTemplateRepository $invoiceTemplateRepository,
        private UserRepository $userRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create invoices')
            ->setHelp('This command allows to create invoices by several different filters.')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The user to be used for generating the invoices')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start date (format: 2020-01-01, default: start of the month)', null)
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End date (format: 2020-01-31, default: end of the month)', null)
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'Timezone for start and end date query (fallback: users timezone)', null)
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of customer IDs', null)
            ->addOption('project', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of project IDs', null)
            ->addOption('by-customer', null, InputOption::VALUE_NONE, 'If set, one invoice for each active customer in the given timerange is created')
            ->addOption('by-project', null, InputOption::VALUE_NONE, 'If set, one invoice for each active project in the given timerange is created')
            ->addOption('set-exported', null, InputOption::VALUE_NONE, '[DEPRECATED] this flag has no meaning any more: invoiced items are always exported')
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, 'Invoice template', null)
            ->addOption('search', null, InputOption::VALUE_OPTIONAL, 'Search term to filter invoice entries', null)
            ->addOption('exported', null, InputOption::VALUE_OPTIONAL, 'Exported filter for invoice entries (possible values: exported, all), by default only "not exported" items are fetched', null)
            ->addOption('preview', null, InputOption::VALUE_OPTIONAL, 'Absolute path for a rendered preview of the invoice, which will neither be saved nor the items be marked as exported.', null)
            ->addOption('preview-unique', null, InputOption::VALUE_NONE, 'Adds a unique part to the filename of the generated invoice preview file, so there is no chance that they get overwritten on same project name.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // =============== VALIDATION START ===============

        $username = $input->getOption('user');
        if (empty($username)) {
            $io->error('You must set a "user" to create invoices');

            return Command::FAILURE;
        }

        try {
            $user = $this->userRepository->loadUserByIdentifier($username);
        } catch (\Exception $exception) {
            $io->error(
                sprintf('The given username "%s" could not be resolved', $username)
            );

            return Command::FAILURE;
        }

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

                return Command::FAILURE;
        }

        $timezone = $input->getOption('timezone');
        if ($timezone === null) {
            $timezone = $user->getTimezone();
        }

        $timezone = new \DateTimeZone($timezone);
        $dateFactory = new DateTimeFactory($timezone, $user->isFirstDayOfWeekSunday());

        if (!empty($input->getOption('start')) && empty($input->getOption('end'))) {
            $io->error('You need to supply a end date if a start date was given');

            return Command::FAILURE;
        }

        $byActiveCustomer = $input->getOption('by-customer');
        $byActiveProject = $input->getOption('by-project');

        if ($byActiveCustomer && $byActiveProject) {
            $io->error('You cannot mix "by-customer" and "by-project"');

            return Command::FAILURE;
        }

        $customersIDs = $input->getOption('customer');
        $projectIDs = $input->getOption('project');
        if (!$byActiveCustomer && !$byActiveProject && empty($customersIDs) && empty($projectIDs)) {
            $io->error('Could not determine generation mode, you need to set one of: customer, project, by-customer, by-project');

            return Command::FAILURE;
        }

        $start = $input->getOption('start');
        if (!empty($start)) {
            try {
                $start = $dateFactory->createDateTime($start);
            } catch (\Exception $ex) {
                $io->error('Invalid start date given');

                return Command::FAILURE;
            }
        }
        if (!$start instanceof \DateTimeInterface) {
            $start = $dateFactory->getStartOfMonth();
        }

        $start = \DateTimeImmutable::createFromInterface($start);
        $start = $start->setTime(0, 0, 0);

        $end = $input->getOption('end');
        if (!empty($end)) {
            try {
                $end = $dateFactory->createDateTime($end);
            } catch (\Exception $ex) {
                $io->error('Invalid end date given');

                return Command::FAILURE;
            }
        }
        if (!$end instanceof \DateTimeInterface) {
            $end = $dateFactory->getEndOfMonth();
        }

        $end = \DateTimeImmutable::createFromInterface($end);
        $end = $end->setTime(23, 59, 59);

        $searchTerm = null;
        if (null !== $input->getOption('search')) {
            $searchTerm = new SearchTerm($input->getOption('search'));
        }

        if ($input->getOption('preview') !== null) {
            $this->previewUniqueFile = (bool) $input->getOption('preview-unique');
            $this->previewDirectory = rtrim($input->getOption('preview'), '/') . '/';
            if (!is_dir($this->previewDirectory) || !is_writable($this->previewDirectory)) {
                $io->error('Invalid preview directory given');

                return Command::FAILURE;
            }
        } elseif ($input->getOption('set-exported')) {
            @trigger_error('The "set-exported" option of kimai:invoice:create command has no meaning anymore, it will be removed soon', E_USER_DEPRECATED);
        }

        // =============== VALIDATION END ===============

        $defaultQuery = new InvoiceQuery();
        $defaultQuery->setBegin($start);
        $defaultQuery->setEnd($end);
        $defaultQuery->setCurrentUser($user);
        $defaultQuery->setSearchTerm($searchTerm);
        $defaultQuery->setExported($exportedFilter);

        /** @var Invoice[] $invoices */
        $invoices = [];

        if (!empty($customersIDs)) {
            /** @var Customer[] $customers */
            $customers = [];

            $customersIDs = explode(',', $customersIDs);
            foreach ($customersIDs as $id) {
                $tmp = $this->customerRepository->find($id);
                if (null === $tmp) {
                    $io->error('Unknown customer ID: ' . $id);

                    return Command::FAILURE;
                }
                $customers[] = $tmp;
            }
            $invoices = $this->createInvoicesForCustomer($customers, $defaultQuery, $input, $output);
        } elseif (!empty($projectIDs)) {
            /** @var Project[] $projects */
            $projects = [];

            $projectIDs = explode(',', $projectIDs);
            foreach ($projectIDs as $id) {
                $tmp = $this->projectRepository->find($id);
                if (null === $tmp) {
                    $io->error('Unknown project ID: ' . $id);

                    return Command::FAILURE;
                }
                $projects[] = $tmp;
            }
            $invoices = $this->createInvoicesForProjects($projects, $defaultQuery, $input, $output);
        } elseif ($byActiveCustomer) {
            $customers = $this->getActiveCustomers($defaultQuery);
            $invoices = $this->createInvoicesForCustomer($customers, $defaultQuery, $input, $output);
        } elseif ($byActiveProject) {
            $projects = $this->getActiveProjects($defaultQuery);
            $invoices = $this->createInvoicesForProjects($projects, $defaultQuery, $input, $output);
        } else {
            $io->error('Could not determine generation mode'); //-///9==8=//99/96//////-*/-*//96* <= by Ayumi

            return Command::FAILURE;
        }

        return $this->renderInvoiceResult($input, $output, $invoices);
    }

    /**
     * @param Project[] $projects
     * @param InvoiceQuery $defaultQuery
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Invoice[]
     * @throws \Exception
     */
    protected function createInvoicesForProjects(array $projects, InvoiceQuery $defaultQuery, InputInterface $input, OutputInterface $output): array
    {
        $io = new SymfonyStyle($input, $output);

        /** @var Invoice[] $invoices */
        $invoices = [];

        foreach ($projects as $project) {
            $customer = $project->getCustomer();
            if ($customer === null) {
                throw new \Exception('Project has no customer: ' . $project->getId());
            }

            $query = clone $defaultQuery;
            $query->addProject($project);
            $query->addCustomer($customer);

            $tpl = $this->getTemplateForCustomer($input, $customer);
            if (null === $tpl) {
                $io->warning(sprintf('Could not find invoice template for project "%s", skipping!', $project->getName()));
                continue;
            }
            $query->setTemplate($tpl);

            try {
                if (null !== $this->previewDirectory) {
                    $invoices[] = $this->saveInvoicePreview($this->serviceInvoice->renderInvoice($this->serviceInvoice->createModel($query), $this->eventDispatcher));
                } else {
                    $invoices[] = $this->serviceInvoice->createInvoice($this->serviceInvoice->createModel($query), $this->eventDispatcher);
                }
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed to create invoice for project "%s" with: %s', $project->getName(), $ex->getMessage()));
            }
        }

        return $invoices;
    }

    private function saveInvoicePreview(Response $response): string
    {
        $filename = uniqid('invoice_');
        $directory = rtrim($this->previewDirectory, '/') . '/';

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
            if ($this->previewUniqueFile) {
                $filename = uniqid('invoice_') . $filename;
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

    /**
     * @param Customer[] $customers
     * @param InvoiceQuery $defaultQuery
     * @param InputInterface $input
     * @return Invoice[]
     * @throws \Exception
     */
    protected function createInvoicesForCustomer(array $customers, InvoiceQuery $defaultQuery, InputInterface $input, OutputInterface $output): array
    {
        $io = new SymfonyStyle($input, $output);

        /** @var Invoice[] $invoices */
        $invoices = [];

        foreach ($customers as $customer) {
            $query = clone $defaultQuery;
            $query->addCustomer($customer);

            $tpl = $this->getTemplateForCustomer($input, $customer);
            if (null === $tpl) {
                $io->warning(sprintf('Could not find invoice template for customer "%s", skipping!', $customer->getName()));
                continue;
            }
            $query->setTemplate($tpl);

            try {
                if (null !== $this->previewDirectory) {
                    $invoices[] = $this->saveInvoicePreview($this->serviceInvoice->renderInvoice($this->serviceInvoice->createModel($query), $this->eventDispatcher));
                } else {
                    $invoices[] = $this->serviceInvoice->createInvoice($this->serviceInvoice->createModel($query), $this->eventDispatcher);
                }
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed to create invoice for customer "%s" with: %s', $customer->getName(), $ex->getMessage()));
            }
        }

        return $invoices;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Invoice[] $invoices
     * @return int
     */
    protected function renderInvoiceResult(InputInterface $input, OutputInterface $output, array $invoices): int
    {
        $io = new SymfonyStyle($input, $output);

        if (empty($invoices)) {
            $io->warning('No invoice was generated');

            return Command::SUCCESS;
        }

        if (null !== $this->previewDirectory) {
            $columns = ['Filename'];

            $table = new Table($output);
            $table->setHeaderTitle(sprintf('Created %s invoice(s)', \count($invoices)));
            $table->setHeaders($columns);

            foreach ($invoices as $invoiceFile) {
                $table->addRow([$invoiceFile]);
            }

            $table->render();

            return Command::SUCCESS;
        }

        $columns = ['ID', 'Customer', 'Total', 'Filename'];

        $table = new Table($output);
        $table->setHeaderTitle(sprintf('Created %s invoice(s)', \count($invoices)));
        $table->setHeaders($columns);

        foreach ($invoices as $invoice) {
            $file = $this->serviceInvoice->getInvoiceFile($invoice);
            if (null === $file) {
                $io->warning(
                    sprintf('Created invoice with ID %s, but file was not found %s', $invoice->getId(), $invoice->getInvoiceFilename())
                );
                continue;
            }

            $table->addRow([
                $invoice->getId(),
                $invoice->getCustomer()->getName(),
                $invoice->getTotal() . ' ' . $invoice->getCustomer()->getCurrency(),
                $file->getRealPath()
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function getTemplateForCustomer(InputInterface $input, Customer $customer): ?InvoiceTemplate
    {
        $template = $input->getOption('template');

        if (null === $template) {
            return $customer->getInvoiceTemplate();
        }

        $tpl = $this->invoiceTemplateRepository->find($template);

        if (null !== $tpl) {
            return $tpl;
        }

        return $this->invoiceTemplateRepository->findOneBy(['name' => $template]);
    }

    /**
     * @param InvoiceQuery $invoiceQuery
     * @return Customer[]
     */
    private function getActiveCustomers(InvoiceQuery $invoiceQuery): array
    {
        $results = $this->serviceInvoice->getInvoiceItems($invoiceQuery);

        $customers = [];

        foreach ($results as $result) {
            $customer = $result->getProject()->getCustomer();
            $customers[$customer->getId()] = $customer;
        }

        return $customers;
    }

    /**
     * @param InvoiceQuery $invoiceQuery
     * @return Project[]
     */
    private function getActiveProjects(InvoiceQuery $invoiceQuery): array
    {
        $results = $this->serviceInvoice->getInvoiceItems($invoiceQuery);

        $projects = [];

        foreach ($results as $result) {
            $project = $result->getProject();
            $projects[$project->getId()] = $project;
        }

        return $projects;
    }
}
