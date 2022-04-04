<?php

namespace ThULB\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;

class DoiLinker extends AbstractHelper {

    protected $pluginManager;
    protected $resolver;

    public function __construct($pluginManager, $resolver) {
        $this->pluginManager = $pluginManager;
        $this->resolver = explode(',', $resolver);
    }

    public function __invoke($doi) {
        $response = [];
        foreach($this->resolver as $resolver) {
            if ($this->pluginManager->has($resolver)) {
                $resolverResponse = $this->pluginManager->get($resolver)->getLinks([$doi]);

                $doiData = array_merge($response[$doi] ?? [], $resolverResponse[$doi] ?? []);
                if(!empty($doiData)) {
                    $response[$doi] = $doiData;
                }
            }
        }

        return $response;
    }
}