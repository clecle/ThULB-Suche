<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters map projection view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class MapProjectionMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Projektion bei Karten';
    protected $metadataKey = 'Map Projection';
    protected $recordDriverFunction = 'getCartographicProjection';
}
