<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters printing places view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class PrintingPlacesMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Druckort';
    protected $metadataKey = 'Printing places';
    protected $recordDriverFunction = 'getPrintingPlaces';
}
