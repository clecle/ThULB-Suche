<?php
namespace ThULBAdmin\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            \ThULBAdmin\Controller\DynMessagesController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \ThULBAdmin\Controller\UserdataController::class => \VuFind\Controller\AbstractBaseFactory::class,
        ),
        'aliases' => array(
            'dynMessages' => \ThULBAdmin\Controller\DynMessagesController::class,
            'DynMessages' => \ThULBAdmin\Controller\DynMessagesController::class,
            'userdata' => \ThULBAdmin\Controller\UserdataController::class,
            'Userdata' => \ThULBAdmin\Controller\UserdataController::class,
        )
    ),
);

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addDynamicRoute($config, 'DynMessages', 'DynMessages', 'edit');
$routeGenerator->addDynamicRoute($config, 'ReassignUserdata', 'Userdata', 'reassign');
$routeGenerator->addDynamicRoute($config, 'DeleteUserdata', 'Userdata', 'delete');

return $config;