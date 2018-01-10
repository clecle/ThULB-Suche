<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters other authors view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */

class OtherAuthorsMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Weitere Verfasser';
    protected $metadataKey = 'Other Authors';
    protected $recordDriverFunction = 'getDeduplicatedAuthors';
    protected $template = 'data-authors.phtml';
    protected $options = [
                    'context' => [
                        'type' => 'secondary',
                        'schemaLabel' => 'contributor',
                        'requiredDataFields' => [
                            ['name' => 'role', 'prefix' => 'CreatorRoles::']
                        ]]
                ];
}
