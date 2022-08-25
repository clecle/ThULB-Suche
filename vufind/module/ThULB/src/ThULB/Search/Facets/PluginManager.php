<?php

namespace ThULB\Search\Facets;

use VuFind\ServiceManager\AbstractPluginManager;

class PluginManager extends AbstractPluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [];

    /**
     * Constructor
     *
     * Make sure plugins are properly initialized.
     *
     * @param mixed $configOrContainerInstance Configuration or container instance
     * @param array $v3config                  If $configOrContainerInstance is a
     *                                         container, this value will be passed
     *                                         to the parent constructor.
     */
    public function __construct($configOrContainerInstance = null,
        array $v3config = []
    ) {
        // These objects are not meant to be shared -- every time we retrieve one,
        // we are building a brand new object.
        $this->sharedByDefault = false;

        $this->addAbstractFactory(PluginFactory::class);
        parent::__construct($configOrContainerInstance, $v3config);
    }

    /**
     * Return the name of the base class or interface that plug-ins must conform to.
     *
     * @return string
     */
    protected function getExpectedInterface() : string
    {
        return IFacet::class;
    }
}
