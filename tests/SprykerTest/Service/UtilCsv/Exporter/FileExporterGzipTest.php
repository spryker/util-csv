<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Service\UtilCsv\Exporter;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CsvFileTransfer;
use Generator;
use Spryker\Service\UtilCsv\Exporter\FileExporter;
use Spryker\Service\UtilCsv\Exporter\FileExporterInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Service
 * @group UtilCsv
 * @group Exporter
 * @group FileExporterGzipTest
 * Add your own group annotations below this line
 *
 * @property \SprykerTest\Service\UtilCsv\UtilCsvServiceTester $tester
 */
class FileExporterGzipTest extends Unit
{
    /**
     * @var list<string>
     */
    protected const SKUS = ['sku1', 'sku2', 'sku3'];

    /**
     * @return void
     */
    public function testExportWithGzipEnabledSetsContentEncodingHeader(): void
    {
        // Arrange
        $csvFileTransfer = (new CsvFileTransfer())
            ->setFileName('test.csv')
            ->addHeader('sku')
            ->addDataGenerator($this->createDataGenerator())
            ->setIsGzipEnabled(true);

        // Act
        $streamedResponse = $this->createFileExporter()->exportFile($csvFileTransfer);

        // Assert
        $this->assertSame('gzip', $streamedResponse->headers->get('Content-Encoding'));
    }

    /**
     * @return void
     */
    public function testExportWithGzipDisabledDoesNotSetContentEncodingHeader(): void
    {
        // Arrange
        $csvFileTransfer = (new CsvFileTransfer())
            ->setFileName('test.csv')
            ->addHeader('sku')
            ->addDataGenerator($this->createDataGenerator())
            ->setIsGzipEnabled(false);

        // Act
        $streamedResponse = $this->createFileExporter()->exportFile($csvFileTransfer);

        // Assert
        $this->assertNull($streamedResponse->headers->get('Content-Encoding'));
    }

    /**
     * @return void
     */
    public function testExportWithGzipEnabledProducesValidGzipOutput(): void
    {
        // Arrange
        $csvFileTransfer = (new CsvFileTransfer())
            ->setFileName('test.csv')
            ->addHeader('sku')
            ->addDataGenerator($this->createDataGenerator())
            ->setIsGzipEnabled(true);

        // Act
        $streamedResponse = $this->createFileExporter()->exportFile($csvFileTransfer);
        ob_start();
        $streamedResponse->sendContent();
        $compressedContent = ob_get_clean();

        // Assert
        $this->assertNotEmpty($compressedContent);
        $decompressed = gzdecode($compressedContent);
        $this->assertNotFalse($decompressed, 'Output should be valid gzip data');

        foreach (static::SKUS as $sku) {
            $this->assertStringContainsString($sku, $decompressed);
        }
    }

    /**
     * @return void
     */
    public function testExportWithGzipProducesSmallerOutputThanPlain(): void
    {
        // Arrange
        $skus = array_map(
            fn (int $i): string => sprintf('product-sku-%05d', $i),
            range(1, 100),
        );

        $plainTransfer = (new CsvFileTransfer())
            ->setFileName('test.csv')
            ->addHeader('sku')
            ->addDataGenerator($this->createDataGeneratorFromSkus($skus))
            ->setIsGzipEnabled(false);

        $gzipTransfer = (new CsvFileTransfer())
            ->setFileName('test.csv')
            ->addHeader('sku')
            ->addDataGenerator($this->createDataGeneratorFromSkus($skus))
            ->setIsGzipEnabled(true);

        // Act
        ob_start();
        $this->createFileExporter()->exportFile($plainTransfer)->sendContent();
        $plainContent = ob_get_clean();

        ob_start();
        $this->createFileExporter()->exportFile($gzipTransfer)->sendContent();
        $gzipContent = ob_get_clean();

        // Assert
        $this->assertGreaterThan(strlen($gzipContent), strlen($plainContent));
    }

    /**
     * @return void
     */
    public function testExportWithoutGzipFlagDoesNotCompressOutput(): void
    {
        // Arrange
        $csvFileTransfer = (new CsvFileTransfer())
            ->setFileName('test.csv')
            ->addHeader('sku')
            ->addDataGenerator($this->createDataGenerator());

        // Act
        $streamedResponse = $this->createFileExporter()->exportFile($csvFileTransfer);
        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        // Assert
        $this->assertNotEmpty($content);

        foreach (static::SKUS as $sku) {
            $this->assertStringContainsString($sku, $content);
        }
    }

    /**
     * @return \Generator<list<string>>
     */
    protected function createDataGenerator(): Generator
    {
        foreach (static::SKUS as $sku) {
            yield [$sku];
        }
    }

    /**
     * @param array<string> $skus
     *
     * @return \Generator<list<string>>
     */
    protected function createDataGeneratorFromSkus(array $skus): Generator
    {
        foreach ($skus as $sku) {
            yield [$sku];
        }
    }

    /**
     * @return \Spryker\Service\UtilCsv\Exporter\FileExporterInterface
     */
    protected function createFileExporter(): FileExporterInterface
    {
        return new FileExporter();
    }
}
