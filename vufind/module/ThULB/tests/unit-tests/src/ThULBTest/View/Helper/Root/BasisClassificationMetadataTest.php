<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters basic classification view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class BasisClassificationMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Basisklassifikation';
    protected $metadataKey = 'Basic Classification';
    protected $template = 'data-basicClassification.phtml';           
}