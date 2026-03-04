<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Service\UtilCsv\Exporter;

use DeflateContext;
use Generated\Shared\Transfer\CsvFileTransfer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileExporter implements FileExporterInterface
{
    public function exportFile(CsvFileTransfer $csvFileTransfer): StreamedResponse
    {
        $csvFileTransfer->requireFileName();
        if (!$csvFileTransfer->getDataGenerators()) {
            $csvFileTransfer->requireData();
        }

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($csvFileTransfer) {
            /** @var resource $csvHandle */
            $csvHandle = fopen('php://output', 'w+');
            $deflateContext = $this->createDeflateContext($csvFileTransfer);

            $this->writeCsvHeader($csvFileTransfer, $csvHandle, $deflateContext);
            $this->writeCsvRow($csvFileTransfer, $csvHandle, $deflateContext);
            $this->writeCsvRowByDataGenerators($csvFileTransfer, $csvHandle, $deflateContext);
            $this->finalizeGzipStream($csvHandle, $deflateContext);

            fclose($csvHandle);
        });

        $this->configureResponseHeaders($streamedResponse, $csvFileTransfer);

        return $streamedResponse;
    }

    /**
     * @param \Generated\Shared\Transfer\CsvFileTransfer $csvFileTransfer
     * @param resource $csvHandle
     * @param \DeflateContext|null $deflateContext
     *
     * @return void
     */
    protected function writeCsvHeader(CsvFileTransfer $csvFileTransfer, $csvHandle, ?DeflateContext $deflateContext = null): void
    {
        /** @phpstan-ignore-next-line */
        if ($csvFileTransfer->getHeader() && is_array($csvFileTransfer->getHeader())) {
            $this->writeRow($csvHandle, $csvFileTransfer->getHeader(), $deflateContext);
        }
    }

    /**
     * @deprecated Use {@link \Spryker\Service\UtilCsv\Exporter\FileExporter::writeCsvRowByDataGenerators()} instead.
     *
     * @param \Generated\Shared\Transfer\CsvFileTransfer $csvFileTransfer
     * @param resource $csvHandle
     * @param \DeflateContext|null $deflateContext
     *
     * @return void
     */
    protected function writeCsvRow(CsvFileTransfer $csvFileTransfer, $csvHandle, ?DeflateContext $deflateContext = null): void
    {
        foreach ($csvFileTransfer->getData() as $csvLineArray) {
            $this->writeRow($csvHandle, $csvLineArray, $deflateContext);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\CsvFileTransfer $csvFileTransfer
     * @param resource $csvHandle
     * @param \DeflateContext|null $deflateContext
     *
     * @return void
     */
    protected function writeCsvRowByDataGenerators(CsvFileTransfer $csvFileTransfer, $csvHandle, ?DeflateContext $deflateContext = null): void
    {
        foreach ($csvFileTransfer->getDataGenerators() as $dataGenerator) {
            foreach ($dataGenerator as $data) {
                $this->writeRow($csvHandle, $data, $deflateContext);
            }
        }
    }

    /**
     * @param resource $csvHandle
     * @param array<string|null> $fields
     * @param \DeflateContext|null $deflateContext
     *
     * @return void
     */
    protected function writeRow($csvHandle, array $fields, ?DeflateContext $deflateContext): void
    {
        if ($deflateContext === null) {
            fputcsv($csvHandle, $fields);

            return;
        }

        /** @var resource $buffer */
        $buffer = fopen('php://memory', 'r+b');
        fputcsv($buffer, $fields);
        rewind($buffer);
        /** @var string $csvLine */
        $csvLine = stream_get_contents($buffer);
        fclose($buffer);

        /** @var string $compressedChunk */
        $compressedChunk = deflate_add($deflateContext, $csvLine, ZLIB_NO_FLUSH);
        fwrite($csvHandle, $compressedChunk);
    }

    protected function createDeflateContext(CsvFileTransfer $csvFileTransfer): ?DeflateContext
    {
        if (!$csvFileTransfer->getIsGzipEnabled()) {
            return null;
        }

        /** @var \DeflateContext $deflateContext */
        $deflateContext = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 1]);

        return $deflateContext;
    }

    /**
     * @param resource $csvHandle
     * @param \DeflateContext|null $deflateContext
     *
     * @return void
     */
    protected function finalizeGzipStream($csvHandle, ?DeflateContext $deflateContext): void
    {
        if ($deflateContext === null) {
            return;
        }

        /** @var string $finalChunk */
        $finalChunk = deflate_add($deflateContext, '', ZLIB_FINISH);
        fwrite($csvHandle, $finalChunk);
    }

    protected function configureResponseHeaders(StreamedResponse $response, CsvFileTransfer $csvFileTransfer): void
    {
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $csvFileTransfer->getFileName() . '"');

        if ($csvFileTransfer->getIsGzipEnabled()) {
            $response->headers->set('Content-Encoding', 'gzip');
        }
    }
}
