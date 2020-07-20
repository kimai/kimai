<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class BinaryFileResponseWriter implements WriterInterface
{
    /**
     * @var WriterInterface
     */
    private $writer;
    /**
     * @var \SplFileInfo
     */
    private $file;
    /**
     * @var string
     */
    private $prefix;

    public function __construct(WriterInterface $writer, string $prefix)
    {
        $this->writer = $writer;
        $this->prefix = $prefix;
    }

    public function getFileExtension(): string
    {
        return $this->writer->getFileExtension();
    }

    public function getContentType(): string
    {
        return $this->writer->getContentType();
    }

    /**
     * {@inheritdoc}
     */
    public function save(Spreadsheet $spreadsheet, array $options = []): \SplFileInfo
    {
        $this->file = $this->writer->save($spreadsheet, $options);

        return $this->file;
    }

    public function getFileResponse(Spreadsheet $spreadsheet, array $options = []): Response
    {
        $file = $this->save($spreadsheet, $options);

        $filename = $this->prefix . '_' . (new \DateTime())->format('Y-m-d_H-i-m') . $this->writer->getFileExtension();

        $response = new BinaryFileResponse($file);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', $this->getContentType());
        $response->headers->set('Content-Disposition', $disposition);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
