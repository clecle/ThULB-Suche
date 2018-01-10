<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters numbering view helper functionality
 * for periodical items.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class NumberingMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Erscheinungsverlauf';
    protected $metadataKey = 'Numbering';
    protected $template = 'data-numbering.phtml';
}
