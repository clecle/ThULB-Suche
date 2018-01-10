<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters map scale view helper functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class MapScaleMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Maßstab bei Karten';
    protected $metadataKey = 'Map Scale';
    protected $recordDriverFunction = 'getCartographicScale';
}
