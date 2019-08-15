<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters other titles view helper
 * functionality.
 */
class OtherTitlesMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Weitere Titel';
    protected $metadataKey = 'Other Titles';
    protected $recordDriverFunction = 'getOtherTitles';
}