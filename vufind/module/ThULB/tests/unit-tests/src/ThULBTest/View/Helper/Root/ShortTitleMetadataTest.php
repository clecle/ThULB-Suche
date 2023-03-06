<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters short title view helper
 * functionality.
 */
class ShortTitleMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'Titel';
    protected ?string $metadataKey = 'keine';
    protected ?string $recordDriverFunction = 'getShortTitle';
}