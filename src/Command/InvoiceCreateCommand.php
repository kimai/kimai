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
use App\Repository\Query\InvoiceQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Utils\SearchTerm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class InvoiceCreateCommand extends Command
{
    /**
     * @var ServiceInvoice
     */
    private $serviceInvoice;
    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var InvoiceTemplateRepository
     */
    private $invoiceTemplateRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var string|null
     */
    private $previewDirectory;

    public function __construct(
        ServiceInvoice $serviceInvoice,
        TimesheetRepository $timesheetRepository,
        CustomerRepository $customerRepository,
        InvoiceTemplateRepository $invoiceTemplateRepository,
        UserRepository $userRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->serviceInvoice = $serviceInvoice;
        $this->timesheetRepository = $timesheetRepository;
        $this->customerRepository = $customerRepository;
        $this->invoiceTemplateRepository = $invoiceTemplateRepository;
        $this->userRepository = $userRepository;
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:invoice:create')
            ->setDescription('Create invoices')
            ->setHelp('This command allows to create invoices by several different filters.')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The user to be used for generating the invoices')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start date (format: 2020-01-01, default: start of the month)', null)
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End date (format: 2020-01-31, default: end of the month)', null)
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'Timezone for start and end date query', date_default_timezone_get())
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of customer IDs', null)
            ->addOption('by-customer', null, InputOption::VALUE_NONE, 'If set, one invoice for each active customer in the given timerange is created')
            ->addOption('by-project', null, InputOption::VALUE_NONE, 'If set, one invoice for each active project in the given timerange is created')
            ->addOption('set-exported', null, InputOption::VALUE_NONE, 'Whether the invoice items should be marked as exported')
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, 'Invoice template', null)
            ->addOption('template-meta', null, InputOption::VALUE_OPTIONAL, 'Fetch invoice template from a meta-field', null)
            ->addOption('search', null, InputOption::VALUE_OPTIONAL, 'Search term to filter invoice entries', null)
            ->addOption('exported', null, InputOption::VALUE_OPTIONAL, 'Exported filter for invoice entries (possible values: exported, all), by default only "not exported" items are fetched', null)
            ->addOption('preview', null, InputOption::VALUE_OPTIONAL, 'Absolute path for a rendered preview of the invoice, which will neither be saved nor the items be marked as exported.', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        // =============== VALIDATION START ===============

        $username = $input->getOption('user');
        if (empty($username)) {
            $io->error('You must set a "user" to create invoices');

            return 1;
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

                return 1;
        }

        $user = $this->userRepository->loadUserByUsername($username);
        if (null === $user) {
            $io->error(
                sprintf('The given username "%s" could not be resolved', $username)
            );

            return 1;
        }

        $timezone = $input->getOption('timezone');
        $timezone = new \DateTimeZone($timezone);

        if (!empty($input->getOption('start')) && empty($input->getOption('end'))) {
            $io->error('You need to supply a end date if a start date was given');

            return 1;
        }

        $byActiveCustomer = $input->getOption('by-customer');
        $byActiveProject = $input->getOption('by-project');

        if ($byActiveCustomer && $byActiveProject) {
            $io->error('You cannot mix "by-customer" and "by-project"');

            return 1;
        }

        $customersIDs = $input->getOption('customer');
        if (!$byActiveCustomer && !$byActiveProject && empty($customersIDs)) {
            $io->error('Could not determine generation mode, you need to set one of: customer, by-customer, by-project');

            return 1;
        }

        if (null === $input->getOption('template') && null === $input->getOption('template-meta')) {
            $io->error('You must either pass the "template" or "template-meta" option');

            return 1;
        }

        $start = $input->getOption('start');
        if (!empty($start)) {
            try {
                $start = new \DateTime($start, $timezone);
            } catch (\Exception $ex) {
                $io->error('Invalid start date given');

                return 1;
            }
        }
        if (!$start instanceof \DateTime) {
            $start = new \DateTime('first day of this month', $timezone);
        }
        $start->setTime(0, 0, 0);

        $end = $input->getOption('end');
        if (!empty($end)) {
            try {
                $end = new \DateTime($end, $timezone);
            } catch (\Exception $ex) {
                $io->error('Invalid end date given');

                return 1;
            }
        }
        if (!$end instanceof \DateTime) {
            $end = new \DateTime('last day of this month', $timezone);
        }
        $end->setTime(23, 59, 59);

        $searchTerm = null;
        if (null !== $input->getOption('search')) {
            $searchTerm = new SearchTerm($input->getOption('search'));
        }

        $markAsExported = false;
        if ($input->getOption('preview') !== null) {
            $this->previewDirectory = rtrim($input->getOption('preview'), '/') . '/';
            if (!is_dir($this->previewDirectory) || !is_writable($this->previewDirectory)) {
                $io->error('Invalid preview directory given');

                return 1;
            }
        } elseif ($input->getOption('set-exported')) {
            $markAsExported = true;
        }

        // =============== VALIDATION END ===============

        $defaultQuery = new InvoiceQuery();
        $defaultQuery->setBegin($start);
        $defaultQuery->setEnd($end);
        $defaultQuery->setCurrentUser($user);
        $defaultQuery->setSearchTerm($searchTerm);
        $defaultQuery->setMarkAsExported($markAsExported);
        $defaultQuery->setState($exportedFilter);

        /** @var Invoice[] $invoices */
        $invoices = [];

        /** @var Customer[] $customers */
        $customers = [];

        if (!empty($customersIDs)) {
            $customersIDs = explode(',', $customersIDs);
            foreach ($customersIDs as $id) {
                $tmp = $this->customerRepository->find($id);
                if (null === $tmp) {
                    $io->error('Unknown customer ID: ' . $id);

                    return 1;
                }
                $customers[] = $tmp;
            }
            $invoices = $this->createInvoicesForCustomer($customers, $defaultQuery, $input, $output);
        } elseif ($byActiveCustomer) {
            $customers = $this->getActiveCustomers($start, $end);
            $invoices = $this->createInvoicesForCustomer($customers, $defaultQuery, $input, $output);
        } elseif ($byActiveProject) {
            $projects = $this->getActiveProjects($start, $end);
            $invoices = $this->createInvoicesForProjects($projects, $defaultQuery, $input, $output);
        } else {
            $io->error('Could not determine generation mode'); //-///9==8=//99/96//////-*/-*//96* <= by Ayumi

            return 1;
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
            $query = clone $defaultQuery;
            $query->addProject($project);
            $query->addCustomer($project->getCustomer());

            $tpl = $this->getTemplateForProject($input, $project);
            if (null === $tpl) {
                $io->warning(sprintf('Could not find invoice template for project "%s", skipping!', $project->getName()));
                continue;
            }
            $query->setTemplate($tpl);

            try {
                if (null !== $this->previewDirectory) {
                    $invoices[] = $this->saveInvoicePreview($this->serviceInvoice->renderInvoice($query, $this->eventDispatcher));
                } else {
                    $invoices[] = $this->serviceInvoice->createInvoice($query, $this->eventDispatcher);
                }
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed to create invoice for project "%s" with: %s', $project->getName(), $ex->getMessage()));
            }
        }

        return $invoices;
    }

    private function saveInvoicePreview(Response $response)
    {
        $filename = uniqid('invoice_');

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
            $file->move($this->previewDirectory, $filename);
        } else {
            (new Filesystem())->dumpFile($this->previewDirectory . $filename, $response->getContent());
        }

        return $this->previewDirectory . $filename;
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
                    $invoices[] = $this->saveInvoicePreview($this->serviceInvoice->renderInvoice($query, $this->eventDispatcher));
                } else {
                    $invoices[] = $this->serviceInvoice->createInvoice($query, $this->eventDispatcher);
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

            return 0;
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

            return 0;
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

        return 0;
    }

    private function getTemplateForCustomer(InputInterface $input, Customer $customer): ?InvoiceTemplate
    {
        $template = $input->getOption('template');

        $meta = $input->getOption('template-meta');
        if (!empty($meta)) {
            $metaField = $customer->getMetaField($meta);
            if (null !== $metaField && !empty($metaField->getValue())) {
                $template = $metaField->getValue();
            }
        }

        if (null === $template) {
            return null;
        }

        return $this->findTemplate($template);
    }

    private function findTemplate(string $idOrName): ?InvoiceTemplate
    {
        $tpl = $this->invoiceTemplateRepository->find($idOrName);

        if (null !== $tpl) {
            return $tpl;
        }

        return $this->invoiceTemplateRepository->findOneBy(['name' => $idOrName]);
    }

    private function getTemplateForProject(InputInterface $input, Project $project): ?InvoiceTemplate
    {
        $template = $this->getTemplateForCustomer($input, $project->getCustomer());

        $meta = $input->getOption('template-meta');
        if (!empty($meta)) {
            $metaField = $project->getMetaField($meta);
            if (null !== $metaField && !empty($metaField->getValue())) {
                $template = $metaField->getValue();
            }
        }

        if (null === $template) {
            return null;
        }

        return $this->findTemplate($template);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return Customer[]
     */
    private function getActiveCustomers(\DateTime $start, \DateTime $end): array
    {
        $query = new TimesheetQuery();
        $query->setBegin($start);
        $query->setEnd($end);

        $results = $this->timesheetRepository->getTimesheetsForQuery($query);

        $customers = [];

        foreach ($results as $result) {
            $customer = $result->getProject()->getCustomer();
            $customers[$customer->getId()] = $customer;
        }

        return $customers;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return Project[]
     */
    private function getActiveProjects(\DateTime $start, \DateTime $end): array
    {
        $query = new TimesheetQuery();
        $query->setBegin($start);
        $query->setEnd($end);

        $results = $this->timesheetRepository->getTimesheetsForQuery($query);

        $projects = [];

        foreach ($results as $result) {
            $project = $result->getProject();
            $projects[$project->getId()] = $project;
        }

        return $projects;
    }
}
