<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters title of work view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class TitleOfWorkMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Werktitel';
    protected $metadataKey = 'Title of work';
    protected $recordDriverFunction = 'getTitleOfWork';
}
