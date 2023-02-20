<?php

namespace DHGETest\View\Helper\Root;

/**
 * Test class for the record data formatters short title view helper
 * functionality.
 */
class SummaryMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'Zusammenfassung';
    protected ?string $recordDriverFunction = 'getSummary';
}