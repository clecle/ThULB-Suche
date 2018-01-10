<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters zdb number view helper functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class ZdbNumberMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'ZDB-Nummer';
    protected $metadataKey = 'ZDB';
    protected $template = 'data-zdb.phtml';
}
