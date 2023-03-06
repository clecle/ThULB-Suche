<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters production view helper functionality.
 */
class ProductionMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'Herstellungsangabe';
    protected ?string $metadataKey = 'Production';
    protected ?string $recordDriverFunction = 'getProduction';
}