<?php

namespace DHGETest\View\Helper\Root;

/**
 * Test class for the record data formatters short title view helper
 * functionality.
 */
class ShortTitleMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Titel';
    protected $metadataKey = 'keine';
    protected $recordDriverFunction = 'getShortTitle';
}