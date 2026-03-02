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
 * @group FileExporterTest
 * Add your own group annotations below this line
 */
class FileExporterTest extends Unit
{
    /**
     * @var list<string>
     */
    protected const SKUS = ['sku1', 'sku2', 'sku3'];

    public function testExportWithDataGenerators(): void
    {
        // Arrange
        $csvFileTransfer = (new CsvFileTransfer())
            ->setFileName('filename')
            ->addHeader('sku')
            ->addDataGenerator($this->getDataExport());

        // Act
        $streamedResponse = $this->createFileExporter()->exportFile($csvFileTransfer);
        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        // Assert
        $this->assertNotNull($content);
        foreach (static::SKUS as $sku) {
            $this->assertStringContainsString($sku, $content);
        }
    }

    /**
     * @return \Generator<list<string>>
     */
    protected function getDataExport(): Generator
    {
        foreach (static::SKUS as $sku) {
            yield [
                $sku,
            ];
        }
    }

    protected function createFileExporter(): FileExporterInterface
    {
        return new FileExporter();
    }
}
