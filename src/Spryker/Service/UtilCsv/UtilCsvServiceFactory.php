<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Service\UtilCsv;

use Spryker\Service\Kernel\AbstractServiceFactory;
use Spryker\Service\UtilCsv\Exporter\FileExporter;
use Spryker\Service\UtilCsv\Exporter\FileExporterInterface;
use Spryker\Service\UtilCsv\Reader\FileReader;
use Spryker\Service\UtilCsv\Reader\FileReaderInterface;

class UtilCsvServiceFactory extends AbstractServiceFactory
{
    public function createFileReader(): FileReaderInterface
    {
        return new FileReader();
    }

    public function createFileExporter(): FileExporterInterface
    {
        return new FileExporter();
    }
}
