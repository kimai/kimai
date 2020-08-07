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
     * @var string
     */
    private $prefix;

    /**
     * @param WriterInterface $writer
     * @param string $prefix is only urlencoded but not validated and can break the response if you pass in invalid character
     */
    public function __construct(WriterInterface $writer, string $prefix)
    {
        $this->writer = $writer;
        $this->prefix = urlencode($prefix);
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
        return $this->writer->save($spreadsheet, $options);
    }

    public function getFileResponse(Spreadsheet $spreadsheet, array $options = []): BinaryFileResponse
    {
        $file = $this->save($spreadsheet, $options);

        $filename = $this->prefix . '_' . (new \DateTime())->format('YmdHim') . '.' . $this->writer->getFileExtension();

        $response = new BinaryFileResponse($file);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', $this->getContentType());
        $response->headers->set('Content-Disposition', $disposition);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
