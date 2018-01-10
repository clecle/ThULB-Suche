<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters numbering pecularitier view helper 
 * functionality for periodical items.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class NumPecMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Anmerkungen zum Erscheinungsverlauf';
    protected $metadataKey = 'NumPecs';
    protected $template = 'data-numbering_peculiarities.phtml';
}
