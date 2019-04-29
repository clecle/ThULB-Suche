<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'recorddataformatter' => 'ThULB\View\Helper\Root\RecordDataFormatterFactory',
            'recordlink' => 'ThULB\View\Helper\Root\Factory::getRecordLink',
            'record' => 'ThULB\View\Helper\Root\Factory::getRecord',
            'thulb_session' => 'ThULB\View\Helper\Root\Factory::getSession',
        ],
    ],
    'favicon' => 'thulb_favicon.ico',
    'js' => array(
            'thulb.js',
            'jquery-ui.min.js',
        ),
);
