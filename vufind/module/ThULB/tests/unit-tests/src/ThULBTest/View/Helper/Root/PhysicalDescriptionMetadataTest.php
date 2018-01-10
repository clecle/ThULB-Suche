<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters physical description view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class PhysicalDescriptionMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Umfang';
    protected $metadataKey = 'Physical Description';
    protected $recordDriverFunction = 'getPhysicalDescriptions';
}
