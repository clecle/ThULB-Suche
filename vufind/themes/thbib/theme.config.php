<?php
return array(
    'extends' => 'thulb',
    'helpers' => [
        'factories' => [
            'ThBIB\View\Helper\Root\RemoveThBibFilter' => 'Zend\ServiceManager\Factory\InvokableFactory',
        ],
        'aliases' => array (
            'thulb_removeThBibFilter' => 'ThBIB\View\Helper\Root\RemoveThBibFilter',
        ),
    ],
);
