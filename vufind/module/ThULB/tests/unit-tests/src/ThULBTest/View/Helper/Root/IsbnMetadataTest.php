<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters isbn view helper functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class IsbnMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'ISBN';
    protected $metadataKey = 'ISBN';
    protected $recordDriverFunction = 'getISBNs';
}
