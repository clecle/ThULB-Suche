<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters map coordinates view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class MapCoordinatesMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Koordinaten bei Karten';
    protected $metadataKey = 'Map Coordinates';
    protected $recordDriverFunction = 'getCartographicCoordinates';
}
