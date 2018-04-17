<?php
return array(
    'extends' => 'thulb',
    'helpers' => [
        'factories' => [
            'recorddataformatter' => 'ThULB\View\Helper\Root\RecordDataFormatterFactory',
            'recordlink' => 'ThULB\View\Helper\Root\Factory::getRecordLink',
            'record' => 'ThULB\View\Helper\Root\Factory::getRecord',
        ],
    ],
    'favicon' => 'dhge_favicon.ico',
);
