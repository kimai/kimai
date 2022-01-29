<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Configuration\LanguageFormattings;
use App\Constants;
use App\Entity\Invoice;
use App\Entity\InvoiceDocument;
use App\Event\InvoiceCreatedEvent;
use App\Event\InvoiceDeleteEvent;
use App\Event\InvoicePostRenderEvent;
use App\Event\InvoicePreRenderEvent;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Timesheet\DateTimeFactory;
use App\Utils\FileHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service to manage invoice dependencies.
 */
final class ServiceInvoice
{
    /**
     * @var CalculatorInterface[]
     */
    private $calculator = [];
    /**
     * @var RendererInterface[]
     */
    private $renderer = [];
    /**
     * @var NumberGeneratorInterface[]
     */
    private $numberGenerator = [];
    /**
     * @var array InvoiceItemRepositoryInterface[]
     */
    private $invoiceItemRepositories = [];
    private $documents;
    private $fileHelper;
    private $formatter;
    private $invoiceRepository;
    private $invoiceModelFactory;

    public function __construct(InvoiceDocumentRepository $repository, FileHelper $fileHelper, InvoiceRepository $invoiceRepository, LanguageFormattings $formatter, InvoiceModelFactory $invoiceModelFactory)
    {
        $this->documents = $repository;
        $this->fileHelper = $fileHelper;
        $this->invoiceRepository = $invoiceRepository;
        $this->formatter = $formatter;
        $this->invoiceModelFactory = $invoiceModelFactory;
    }

    public function addNumberGenerator(NumberGeneratorInterface $generator): ServiceInvoice
    {
        $this->numberGenerator[] = $generator;

        return $this;
    }

    /**
     * @return NumberGeneratorInterface[]
     */
    public function getNumberGenerator(): array
    {
        return $this->numberGenerator;
    }

    public function getNumberGeneratorByName(string $name): ?NumberGeneratorInterface
    {
        foreach ($this->getNumberGenerator() as $generator) {
            if ($generator->getId() === $name) {
                // several models can co-exist at the same time and NumberGeneratorInterface works
                // with setModel() instead of __construct() - returning the same instance would lead to bugs!
                return clone $generator;
            }
        }

        return null;
    }

    public function addCalculator(CalculatorInterface $calculator): ServiceInvoice
    {
        $this->calculator[] = $calculator;

        return $this;
    }

    /**
     * @return CalculatorInterface[]
     */
    public function getCalculator(): array
    {
        return $this->calculator;
    }

    public function getCalculatorByName(string $name): ?CalculatorInterface
    {
        foreach ($this->getCalculator() as $calculator) {
            if ($calculator->getId() === $name) {
                // several models can co-exist at the same time and CalculatorInterface works
                // with setModel() instead of __construct() - returning the same instance would lead to bugs!
                return clone $calculator;
            }
        }

        return null;
    }

    public function getDocumentByName(string $name): ?InvoiceDocument
    {
        return $this->documents->findByName($name);
    }

    /**
     * Returns an array of invoice renderer, which will consist of a unique name and a controller action.
     *
     * @param bool $customOnly
     * @return InvoiceDocument[]
     */
    public function getDocuments(bool $customOnly = false): array
    {
        if ($customOnly) {
            return $this->documents->findCustom();
        }

        return $this->documents->findAll();
    }

    public function addRenderer(RendererInterface $renderer): ServiceInvoice
    {
        $this->renderer[] = $renderer;

        return $this;
    }

    /**
     * Returns an array of invoice renderer.
     *
     * @return RendererInterface[]
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }

    /**
     * @return InvoiceItemRepositoryInterface[]
     */
    public function getInvoiceItemRepositories(): array
    {
        return $this->invoiceItemRepositories;
    }

    public function addInvoiceItemRepository(InvoiceItemRepositoryInterface $invoiceItemRepository): ServiceInvoice
    {
        $this->invoiceItemRepositories[] = $invoiceItemRepository;

        return $this;
    }

    private function getInvoicesDirectory(): string
    {
        return $this->fileHelper->getDataDirectory('invoices');
    }

    public function getInvoiceFile(Invoice $invoice): ?\SplFileInfo
    {
        $invoiceDirectory = $this->getInvoicesDirectory();
        $filename = $invoice->getInvoiceFilename();
        $full = $invoiceDirectory . $filename;

        if (is_file($full) && is_readable($full)) {
            return new \SplFileInfo($full);
        }

        return null;
    }

    public function saveGeneratedInvoice(InvoicePostRenderEvent $event): string
    {
        $invoiceDirectory = $this->getInvoicesDirectory();
        $filename = (string) new InvoiceFilename($event->getModel());

        $response = $event->getResponse();

        if ($event->getResponse()->headers->has('Content-Disposition')) {
            $disposition = $event->getResponse()->headers->get('Content-Disposition');
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
        } else {
            $disposition = $event->getResponse()->headers->get('Content-Type');
            $parts = explode(';', $disposition);
            $parts = explode('/', $parts[0]);
            $filename .= '.' . $parts[1];
        }

        if (is_file($invoiceDirectory . $filename)) {
            throw new \Exception(sprintf('Invoice "%s" already exists', $filename));
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();
            $file->move($invoiceDirectory, $filename);
        } else {
            $this->fileHelper->saveFile($invoiceDirectory . $filename, $event->getResponse()->getContent());
        }

        return $filename;
    }

    public function changeInvoiceStatus(Invoice $invoice, string $status)
    {
        switch ($status) {
            case Invoice::STATUS_NEW:
                $invoice->setIsNew();
                break;

            case Invoice::STATUS_PENDING:
                $invoice->setIsPending();
                break;

            case Invoice::STATUS_PAID:
                $invoice->setIsPaid();
                break;

            case Invoice::STATUS_CANCELED:
                $invoice->setIsCanceled();
                break;

            default:
                throw new \InvalidArgumentException('Unknown invoice status');
        }

        $this->invoiceRepository->saveInvoice($invoice);
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceItemInterface[]
     * @deprecated since 1.14 and will be removed with 2.0
     */
    public function findInvoiceItems(InvoiceQuery $query): array
    {
        @trigger_error('Using findInvoiceItems() is deprecated since 1.14 and will be removed with 2.0', E_USER_DEPRECATED);

        // customer needs to be defined, as we need the currency for the invoice
        if (!$query->hasCustomers()) {
            return [];
        }

        return $this->getInvoiceItems($query);
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceItemInterface[]
     */
    public function getInvoiceItems(InvoiceQuery $query): array
    {
        $items = [];

        foreach ($this->getInvoiceItemRepositories() as $repository) {
            $items = array_merge($items, $repository->getInvoiceItemsForQuery($query));
        }

        return $items;
    }

    private function getDateTimeFactory(InvoiceQuery $query): DateTimeFactory
    {
        $timezone = date_default_timezone_get();
        $sunday = false;

        if (null !== ($user = $query->getCurrentUser())) {
            $timezone = $user->getTimezone();
            $sunday = $user->isFirstDayOfWeekSunday();
        }

        return new DateTimeFactory(new \DateTimeZone($timezone), $sunday);
    }

    /**
     * @param InvoiceItemInterface[] $entries
     */
    private function markEntriesAsExported(array $entries)
    {
        foreach ($this->getInvoiceItemRepositories() as $repository) {
            $repository->setExported($entries);
        }
    }

    public function renderInvoiceWithModel(InvoiceModel $model, EventDispatcherInterface $dispatcher): Response
    {
        $document = $this->getDocumentByName($model->getTemplate()->getRenderer());
        if (null === $document) {
            throw new \Exception('Unknown invoice document: ' . $model->getTemplate()->getRenderer());
        }

        foreach ($this->getRenderer() as $renderer) {
            if ($renderer->supports($document)) {
                $dispatcher->dispatch(new InvoicePreRenderEvent($model, $document, $renderer));

                $response = $renderer->render($document, $model);

                $dispatcher->dispatch(new InvoicePostRenderEvent($model, $document, $renderer, $response));

                return $response;
            }
        }

        throw new \Exception(
            sprintf('Cannot render invoice: %s (%s)', $model->getTemplate()->getRenderer(), $document->getName())
        );
    }

    public function renderInvoice(InvoiceQuery $query, EventDispatcherInterface $dispatcher): Response
    {
        $model = $this->createModel($query);

        return $this->renderInvoiceWithModel($model, $dispatcher);
    }

    /**
     * @param InvoiceModel $model
     * @param EventDispatcherInterface $dispatcher
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoiceFromModel(InvoiceModel $model, EventDispatcherInterface $dispatcher): Invoice
    {
        $document = $this->getDocumentByName($model->getTemplate()->getRenderer());
        if (null === $document) {
            throw new \Exception('Unknown invoice document: ' . $model->getTemplate()->getRenderer());
        }

        foreach ($this->getRenderer() as $renderer) {
            if ($renderer->supports($document)) {
                $dispatcher->dispatch(new InvoicePreRenderEvent($model, $document, $renderer));

                if ($this->invoiceRepository->hasInvoice($model->getInvoiceNumber())) {
                    throw new DuplicateInvoiceNumberException($model->getInvoiceNumber());
                }

                $response = $renderer->render($document, $model);

                $event = new InvoicePostRenderEvent($model, $document, $renderer, $response);
                $dispatcher->dispatch($event);

                $invoiceFilename = $this->saveGeneratedInvoice($event);

                $invoice = new Invoice();
                $invoice->setModel($model);
                $invoice->setFilename($invoiceFilename);
                $this->invoiceRepository->saveInvoice($invoice);

                if ($model->getQuery()->isMarkAsExported()) {
                    $this->markEntriesAsExported($model->getEntries());
                }

                $dispatcher->dispatch(new InvoiceCreatedEvent($invoice, $model));

                return $invoice;
            }
        }

        throw new \Exception(
            sprintf('Cannot render invoice: %s (%s)', $model->getTemplate()->getRenderer(), $document->getName())
        );
    }

    /**
     * @param InvoiceQuery $query
     * @param EventDispatcherInterface $dispatcher
     * @return Invoice[]
     * @throws \Exception
     */
    public function createInvoices(InvoiceQuery $query, EventDispatcherInterface $dispatcher): array
    {
        $invoices = [];

        $models = $this->createModels($query);
        foreach ($models as $model) {
            $invoices[] = $this->createInvoiceFromModel($model, $dispatcher);
        }

        return $invoices;
    }

    /**
     * @param InvoiceQuery $query
     * @param EventDispatcherInterface $dispatcher
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoice(InvoiceQuery $query, EventDispatcherInterface $dispatcher): Invoice
    {
        $model = $this->createModel($query);

        return $this->createInvoiceFromModel($model, $dispatcher);
    }

    public function deleteInvoice(Invoice $invoice, EventDispatcherInterface $dispatcher)
    {
        $invoiceDirectory = $this->getInvoicesDirectory();

        if (is_file($invoiceDirectory . $invoice->getInvoiceFilename())) {
            $this->fileHelper->removeFile($invoiceDirectory . $invoice->getInvoiceFilename());
        }

        $event = new InvoiceDeleteEvent($invoice);
        $dispatcher->dispatch($event);

        $this->invoiceRepository->deleteInvoice($invoice);
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceModel
     * @throws \Exception
     */
    public function createModel(InvoiceQuery $query): InvoiceModel
    {
        $model = $this->createModelWithoutEntries($query);
        $model->addEntries($this->getInvoiceItems($query));

        $this->prepareModelQueryDates($model);

        return $model;
    }

    private function createModelWithoutEntries(InvoiceQuery $query): InvoiceModel
    {
        $template = $query->getTemplate();

        if (!$query->hasCustomers()) {
            throw new \Exception('Cannot create invoice model without customer');
        }

        if (null === $template) {
            throw new \Exception('Cannot create invoice model without template');
        }

        if (null === $template->getLanguage()) {
            $template->setLanguage(Constants::DEFAULT_LOCALE);
            @trigger_error('Using invoice templates without a language is is deprecated and trigger and will throw an exception with 2.0', E_USER_DEPRECATED);
        }

        $formatter = new DefaultInvoiceFormatter($this->formatter, $template->getLanguage());

        $model = $this->invoiceModelFactory->createModel($formatter);
        $model
            ->setTemplate($template)
            ->setInvoiceDate($this->getDateTimeFactory($query)->createDateTime())
            ->setQuery($query)
        ;

        if (null !== $query->getCurrentUser()) {
            $model->setUser($query->getCurrentUser());
        }

        $model->setCustomer($query->getCustomers()[0]);

        $generator = $this->getNumberGeneratorByName($query->getTemplate()->getNumberGenerator());
        if (null === $generator) {
            throw new \Exception('Unknown number generator: ' . $query->getTemplate()->getNumberGenerator());
        }

        $calculator = $this->getCalculatorByName($query->getTemplate()->getCalculator());
        if (null === $calculator) {
            throw new \Exception('Unknown invoice calculator: ' . $query->getTemplate()->getCalculator());
        }

        $model->setCalculator($calculator);
        $model->setNumberGenerator($generator);

        return $model;
    }

    private function prepareModelQueryDates(InvoiceModel $model)
    {
        $begin = $model->getQuery()->getBegin();
        $end = $model->getQuery()->getEnd();

        if ($begin !== null && $end !== null) {
            return;
        }

        if (\count($model->getEntries()) === 0) {
            return;
        }

        $tmpBegin = null;
        $tmpEnd = null;

        foreach ($model->getEntries() as $entry) {
            if ($begin === null) {
                if ($tmpBegin === null) {
                    $tmpBegin = $entry->getBegin();
                } else {
                    $tmpBegin = min($entry->getBegin(), $tmpBegin);
                }
            }

            if ($end === null) {
                if ($tmpEnd === null) {
                    $tmpEnd = $entry->getEnd();
                } else {
                    $tmpEnd = max($entry->getEnd(), $tmpEnd);
                }
            }
        }

        if ($begin === null && $tmpBegin !== null) {
            $model->getQuery()->setBegin($tmpBegin);
        }

        if ($end === null && $tmpEnd !== null) {
            $model->getQuery()->setEnd($tmpEnd);
        }
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceModel[]
     * @throws \Exception
     */
    public function createModels(InvoiceQuery $query): array
    {
        $models = [];
        $customerEntries = [];
        $items = $this->getInvoiceItems($query);

        foreach ($items as $entry) {
            $customer = $entry->getProject()->getCustomer();
            $id = $customer->getId();
            if (!\array_key_exists($id, $customerEntries)) {
                $customerEntries[$id] = [
                    'customer' => $customer,
                    'entries' => [],
                ];
            }
            $customerEntries[$id]['entries'][] = $entry;
        }

        if (empty($customerEntries)) {
            return [];
        }

        uasort($customerEntries, function ($a, $b) {
            return strcmp($a['customer']->getName(), $b['customer']->getName());
        });

        foreach ($customerEntries as $id => $settings) {
            $customerQuery = clone $query;
            $customerQuery->setCustomers([$settings['customer']]);
            $model = $this->createModelWithoutEntries($customerQuery);
            $model->addEntries($settings['entries']);
            $this->prepareModelQueryDates($model);

            $models[] = $model;
        }

        return $models;
    }
}
