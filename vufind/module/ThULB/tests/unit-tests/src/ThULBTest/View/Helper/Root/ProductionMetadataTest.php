<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters production view helper functionality.
 */
class ProductionMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Herstellungsangabe';
    protected $metadataKey = 'Production';
    protected $recordDriverFunction = 'getProduction';
}