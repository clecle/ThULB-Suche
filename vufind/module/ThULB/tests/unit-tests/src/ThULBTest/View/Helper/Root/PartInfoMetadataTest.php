<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters part information view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class PartInfoMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Teil und Abteilung';
    protected $metadataKey = 'PartInfo';
    protected $recordDriverFunction = 'getPartInfo';      
}
