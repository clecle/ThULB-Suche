<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters bibliographic citations view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class BibliographicCitationsMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Bibliographische Zitate';
    protected $metadataKey = 'Bibliographic Citations';
    protected $recordDriverFunction = 'getBibliographicCitation';      
}
