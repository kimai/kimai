<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Configuration\LocaleService;
use App\Entity\ExportableItem;
use App\Entity\Invoice;
use App\Event\InvoiceCreatedEvent;
use App\Event\InvoiceDeleteEvent;
use App\Event\InvoicePostRenderEvent;
use App\Event\InvoicePreRenderEvent;
use App\Export\Base\DispositionInlineInterface;
use App\Model\InvoiceDocument;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
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
    private array $calculator = [];
    /**
     * @var RendererInterface[]
     */
    private array $renderer = [];
    /**
     * @var NumberGeneratorInterface[]
     */
    private array $numberGenerator = [];
    /**
     * @var array InvoiceItemRepositoryInterface[]
     */
    private array $invoiceItemRepositories = [];

    public function __construct(
        private InvoiceDocumentRepository $documents,
        private FileHelper $fileHelper,
        private InvoiceRepository $invoiceRepository,
        private LocaleService $formatter,
        private InvoiceModelFactory $invoiceModelFactory
    ) {
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
     * @return ExportableItem[]
     */
    public function getInvoiceItems(InvoiceQuery $query): array
    {
        $items = [];

        foreach ($this->getInvoiceItemRepositories() as $repository) {
            $items = array_merge($items, $repository->getInvoiceItemsForQuery($query));
        }

        return $items;
    }

    /**
     * @param ExportableItem[] $entries
     */
    private function markEntriesAsExported(array $entries)
    {
        foreach ($this->getInvoiceItemRepositories() as $repository) {
            $repository->setExported($entries);
        }
    }

    public function renderInvoice(InvoiceModel $model, EventDispatcherInterface $dispatcher, bool $dispositionInline = false): Response
    {
        $document = $this->getDocumentByName($model->getTemplate()->getRenderer());
        if (null === $document) {
            throw new \Exception('Please adjust your invoice template, the renderer is invalid: ' . $model->getTemplate()->getRenderer());
        }

        foreach ($this->getRenderer() as $renderer) {
            if ($renderer->supports($document)) {
                $dispatcher->dispatch(new InvoicePreRenderEvent($model, $document, $renderer));

                if ($renderer instanceof DispositionInlineInterface) {
                    $renderer->setDispositionInline($dispositionInline);
                }

                $response = $renderer->render($document, $model);

                $dispatcher->dispatch(new InvoicePostRenderEvent($model, $document, $renderer, $response));

                return $response;
            }
        }

        throw new \Exception(
            sprintf('Cannot render invoice: %s (%s)', $model->getTemplate()->getRenderer(), $document->getName())
        );
    }

    /**
     * @param InvoiceModel $model
     * @param EventDispatcherInterface $dispatcher
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoice(InvoiceModel $model, EventDispatcherInterface $dispatcher): Invoice
    {
        $document = $this->getDocumentByName($model->getTemplate()->getRenderer());
        if (null === $document) {
            throw new \Exception('Unknown invoice document: ' . $model->getTemplate()->getRenderer());
        }

        foreach ($this->getRenderer() as $renderer) {
            if ($renderer->supports($document)) {
                $preEvent = new InvoicePreRenderEvent($model, $document, $renderer);
                $dispatcher->dispatch($preEvent);

                if ($preEvent->isPropagationStopped()) {
                    continue;
                }

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

                if (!$invoice->getCustomer()->hasInvoiceTemplate()) {
                    $invoice->getCustomer()->setInvoiceTemplate($model->getTemplate());
                }
                $this->invoiceRepository->saveInvoice($invoice);

                $this->markEntriesAsExported($model->getEntries());
                $dispatcher->dispatch(new InvoiceCreatedEvent($invoice, $model));

                return $invoice;
            }
        }

        throw new \Exception(
            sprintf('Cannot render invoice: %s (%s)', $model->getTemplate()->getRenderer(), $document->getName())
        );
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
        $customer = $query->getCustomer();
        if ($customer === null) {
            throw new \Exception('Cannot create invoice model without customer');
        }

        $template = $query->getTemplate();

        if ($query->isAllowTemplateOverwrite() && $customer->hasInvoiceTemplate()) {
            $template = $customer->getInvoiceTemplate();
        }

        if (null === $template) {
            throw new \Exception('Cannot create invoice model without template');
        }

        $formatter = new DefaultInvoiceFormatter($this->formatter, $template->getLanguage());

        $model = $this->invoiceModelFactory->createModel($formatter);
        $model
            ->setCustomer($customer)
            ->setTemplate($template)
            ->setQuery($query)
        ;

        if ($query->getInvoiceDate() !== null) {
            $model->setInvoiceDate($query->getInvoiceDate());
        }

        if (null !== $query->getCurrentUser()) {
            $model->setUser($query->getCurrentUser());
        }

        $generator = $this->getNumberGeneratorByName($template->getNumberGenerator());
        if (null === $generator) {
            throw new \Exception('Please adjust your invoice template, the number generator is invalid: ' . $template->getNumberGenerator());
        }

        $calculator = $this->getCalculatorByName($template->getCalculator());
        if (null === $calculator) {
            throw new \Exception('Please adjust your invoice template, the sum calculator is invalid: ' . $template->getCalculator());
        }

        $model->setCalculator($calculator);
        $model->setNumberGenerator($generator);

        return $model;
    }

    private function prepareModelQueryDates(InvoiceModel $model): void
    {
        $begin = $model->getQuery()?->getBegin();
        $end = $model->getQuery()?->getEnd();

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
            if ($customer === null || !$customer->isVisible()) { // generating invoices for hidden customers does not yet work
                continue;
            }
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

        uasort($customerEntries, function ($a, $b): int {
            $nameA = $a['customer']->getName();
            $nameB = $b['customer']->getName();

            if ($nameA === null && $nameB === null) {
                $result = 0;
            } elseif ($nameA === null && $nameB !== null) {
                $result = 1;
            } elseif ($nameA !== null && $nameB === null) {
                $result = -1;
            } else {
                $result = strcmp($nameA, $nameB);
            }

            return $result;
        });

        foreach ($customerEntries as $settings) {
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
