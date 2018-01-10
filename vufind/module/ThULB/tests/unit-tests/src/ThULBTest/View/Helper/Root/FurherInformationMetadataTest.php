<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters further information view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class FurherInformationMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Mehr zum Titel';
    protected $metadataKey = 'Online Access';
    protected $template = 'data-onlineAccess.phtml';           
}