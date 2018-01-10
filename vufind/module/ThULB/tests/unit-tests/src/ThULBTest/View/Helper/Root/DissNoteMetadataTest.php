<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters dissertation note view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class DissNoteMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Hochschulschriftenvermerk';
    protected $metadataKey = 'Dissertation';
    protected $recordDriverFunction = 'getDissertationNote';
}
