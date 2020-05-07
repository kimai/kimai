<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Invoice;
use App\Entity\InvoiceDocument;
use App\Event\InvoiceCreatedEvent;
use App\Event\InvoicePostRenderEvent;
use App\Event\InvoicePreRenderEvent;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Timesheet\UserDateTimeFactory;
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
    /**
     * @var InvoiceDocumentRepository
     */
    private $documents;
    /**
     * @var FileHelper
     */
    private $fileHelper;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTimeFactory;
    /**
     * @var InvoiceFormatter
     */
    private $formatter;
    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    public function __construct(InvoiceDocumentRepository $repository, FileHelper $fileHelper, InvoiceRepository $invoiceRepository, UserDateTimeFactory $dateTimeFactory, InvoiceFormatter $formatter)
    {
        $this->documents = $repository;
        $this->fileHelper = $fileHelper;
        $this->invoiceRepository = $invoiceRepository;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->formatter = $formatter;
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
                return $generator;
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
                return $calculator;
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
     * @return InvoiceDocument[]
     */
    public function getDocuments(): array
    {
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
        if (!\in_array($status, [Invoice::STATUS_NEW, Invoice::STATUS_PENDING, Invoice::STATUS_PAID])) {
            throw new \InvalidArgumentException('Unknown invoice status');
        }

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
        }

        $this->invoiceRepository->saveInvoice($invoice);
    }

    /**
     * @param InvoiceQuery $query
     * @return array<string, InvoiceItemInterface[]>
     */
    private function findInvoiceItemsWithRepository(InvoiceQuery $query): array
    {
        // customer needs to be defined, as we need the currency for the invoice
        if (!$query->hasCustomers()) {
            return [];
        }

        if (null === $query->getBegin()) {
            $query->setBegin($this->dateTimeFactory->createDateTime('first day of this month'));
        }
        if (null === $query->getEnd()) {
            $query->setEnd($this->dateTimeFactory->createDateTime('last day of this month'));
        }
        $query->getBegin()->setTime(0, 0, 0);
        $query->getEnd()->setTime(23, 59, 59);

        $repositories = $this->getInvoiceItemRepositories();
        $items = [];

        foreach ($repositories as $repository) {
            $items[\get_class($repository)] = $repository->getInvoiceItemsForQuery($query);
        }

        return $items;
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceItemInterface[]
     */
    public function findInvoiceItems(InvoiceQuery $query): array
    {
        $entries = [];
        $temp = $this->findInvoiceItemsWithRepository($query);

        foreach ($temp as $repo => $items) {
            $entries = array_merge($entries, $items);
        }

        return $entries;
    }

    /**
     * @param array<string, InvoiceItemInterface[]> $entries
     */
    private function markEntriesAsExported(iterable $entries)
    {
        $repositories = $this->getInvoiceItemRepositories();

        foreach ($entries as $repo => $items) {
            foreach ($repositories as $repository) {
                if (\get_class($repository) === $repo) {
                    $repository->setExported($items);
                }
            }
        }
    }

    public function renderInvoice(InvoiceQuery $query, EventDispatcherInterface $dispatcher): Response
    {
        $entries = $this->findInvoiceItemsWithRepository($query);
        $model = $this->createModel($query);
        foreach ($entries as $repo => $items) {
            $model->addEntries($items);
        }

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

    /**
     * @param InvoiceQuery $query
     * @param EventDispatcherInterface $dispatcher
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoice(InvoiceQuery $query, EventDispatcherInterface $dispatcher): Invoice
    {
        $entries = $this->findInvoiceItemsWithRepository($query);
        $model = $this->createModel($query);
        foreach ($entries as $repo => $items) {
            $model->addEntries($items);
        }

        $document = $this->getDocumentByName($model->getTemplate()->getRenderer());
        if (null === $document) {
            throw new \Exception('Unknown invoice document: ' . $model->getTemplate()->getRenderer());
        }

        foreach ($this->getRenderer() as $renderer) {
            if ($renderer->supports($document)) {
                $dispatcher->dispatch(new InvoicePreRenderEvent($model, $document, $renderer));

                $response = $renderer->render($document, $model);

                if ($query->isMarkAsExported()) {
                    $this->markEntriesAsExported($entries);
                }

                $event = new InvoicePostRenderEvent($model, $document, $renderer, $response);
                $dispatcher->dispatch($event);

                $invoiceFilename = $this->saveGeneratedInvoice($event);

                $invoice = new Invoice();
                $invoice->setModel($model);
                $invoice->setFilename($invoiceFilename);
                $this->invoiceRepository->saveInvoice($invoice);

                $dispatcher->dispatch(new InvoiceCreatedEvent($invoice));

                return $invoice;
            }
        }

        throw new \Exception(
            sprintf('Cannot render invoice: %s (%s)', $model->getTemplate()->getRenderer(), $document->getName())
        );
    }

    public function deleteInvoice(Invoice $invoice)
    {
        $invoiceDirectory = $this->getInvoicesDirectory();
        if (is_file($invoiceDirectory . $invoice->getInvoiceFilename())) {
            $this->fileHelper->removeFile($invoiceDirectory . $invoice->getInvoiceFilename());
        }
        $this->invoiceRepository->deleteInvoice($invoice);
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceModel
     * @throws \Exception
     */
    public function createModel(InvoiceQuery $query): InvoiceModel
    {
        $model = new InvoiceModel($this->formatter);
        $model
            ->setInvoiceDate($this->dateTimeFactory->createDateTime())
            ->setQuery($query)
        ;

        if (null !== $query->getCurrentUser()) {
            $model->setUser($query->getCurrentUser());
        }

        if ($query->hasCustomers()) {
            $model->setCustomer($query->getCustomers()[0]);
        }

        if ($query->getTemplate() !== null) {
            $generator = $this->getNumberGeneratorByName($query->getTemplate()->getNumberGenerator());
            if (null === $generator) {
                throw new \Exception('Unknown number generator: ' . $query->getTemplate()->getNumberGenerator());
            }

            $calculator = $this->getCalculatorByName($query->getTemplate()->getCalculator());
            if (null === $calculator) {
                throw new \Exception('Unknown invoice calculator: ' . $query->getTemplate()->getCalculator());
            }

            $model->setTemplate($query->getTemplate());
            $model->setCalculator($calculator);
            $model->setNumberGenerator($generator);
        }

        return $model;
    }
}
