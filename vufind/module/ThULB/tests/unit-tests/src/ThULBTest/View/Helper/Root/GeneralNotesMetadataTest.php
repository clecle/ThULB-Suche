<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters general notes view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class GeneralNotesMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Anmerkungen';
    protected $metadataKey = 'Item Description';
    protected $recordDriverFunction = 'getGeneralNotes';
}
