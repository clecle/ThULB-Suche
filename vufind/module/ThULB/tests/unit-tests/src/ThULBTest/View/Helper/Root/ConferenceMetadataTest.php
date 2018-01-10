<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters conference view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */

class ConferenceMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Konferenz';
    protected $metadataKey = 'Conference';
    protected $recordDriverFunction = 'getMeetingNames';
}
