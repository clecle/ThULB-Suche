<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters invalid isbn view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class InvalidIsbnMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Falsche ISBN';
    protected $metadataKey = 'Invalid ISBN';
    protected $recordDriverFunction = 'getInvalidISBNs';
}
