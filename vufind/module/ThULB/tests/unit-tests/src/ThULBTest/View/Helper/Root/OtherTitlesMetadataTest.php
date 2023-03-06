<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters other titles view helper
 * functionality.
 */
class OtherTitlesMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'Weitere Titel';
    protected ?string $metadataKey = 'Other Titles';
    protected ?string $recordDriverFunction = 'getOtherTitles';
}