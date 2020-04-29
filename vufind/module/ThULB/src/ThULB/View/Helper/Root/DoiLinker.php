<?php

namespace ThULB\View\Helper\Root;

use Zend\View\Helper\AbstractHelper;

class DoiLinker extends AbstractHelper {

    protected $pluginManager;
    protected $resolver;

    public function __construct($pluginManager, $resolver) {
        $this->pluginManager = $pluginManager;
        $this->resolver = $resolver;
    }

    public function __invoke($doi) {
        $response = [];
        if ($this->pluginManager->has($this->resolver)) {
            $response = $this->pluginManager->get($this->resolver)->getLinks([$doi]);
        }

        return $response;
    }
}