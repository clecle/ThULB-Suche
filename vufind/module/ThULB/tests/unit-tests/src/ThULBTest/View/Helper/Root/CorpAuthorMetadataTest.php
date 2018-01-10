<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters corporate author view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */

class CorpAuthorMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Körperschaft';
    protected $metadataKey = 'Corporate Author';
    protected $recordDriverFunction = 'getDeduplicatedAuthors';
    protected $template = 'data-authors.phtml';
    protected $options = [
                    'context' => [
                        'type' => 'corporate',
                        'schemaLabel' => 'creator',
                        'requiredDataFields' => [
                            ['name' => 'role', 'prefix' => 'CreatorRoles::']
                        ]]
                ];
}
