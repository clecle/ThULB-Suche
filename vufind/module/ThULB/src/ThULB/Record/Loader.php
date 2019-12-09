<?php

namespace ThULB\Record;

use Exception;
use VuFind\Exception\RecordMissing as RecordMissingException;
use VuFind\Record\Loader as OriginalLoader;
use VuFind\RecordDriver\AbstractBase;
use VuFindSearch\ParamBag;

class Loader extends OriginalLoader
{
    /**
     * @var int Maximum number of tries to load the record
     */
    protected $maxTries = 5;

    /**
     * @var array Counter to keep track of attempts to load records. Array keys contain the
     *            source and id of a record (source:id) and the values are the tries.
     */
    protected $tryCounter = array();

    /**
     * Given an ID and record source, load the requested record object.
     *
     * @param string   $id              Record ID
     * @param string   $source          Record source
     * @param bool     $tolerateMissing Should we load a "Missing" placeholder
     * instead of throwing an exception if the record cannot be found?
     * @param ParamBag $params          Search backend parameters
     *
     * @throws Exception
     * @return AbstractBase
     */
    public function load($id, $source = DEFAULT_SEARCH_BACKEND, $tolerateMissing = true, ParamBag $params = null) {
        $key = $source . ':' . $id;
        if (!isset($this->tryCounter[$key])) {
            $this->tryCounter[$key] = 0;
        }
        $this->tryCounter[$key]++;

        if ($this->tryCounter[$key] > $this->maxTries) {
            throw new RecordMissingException(
                'Record ' . $source . ':' . $id . ' does not exist.'
            );
        }

        return parent::load($id, $source, $tolerateMissing, $params);
    }
}
