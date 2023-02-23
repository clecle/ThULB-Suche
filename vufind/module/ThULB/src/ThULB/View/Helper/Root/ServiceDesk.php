<?php

namespace ThULB\View\Helper\Root;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;

class ServiceDesk extends AbstractHelper
{
    protected string $baseUrl;
    protected bool $enabled;
    protected array $fields = array();
    protected array $forms = array();

    public function __construct(Config $config) {
        $this->enabled = $config->ServiceDesk->enabled;

        if($this->enabled) {
            $this->baseUrl = $config->ServiceDesk->baseUrl;

            $this->forms = $config->ServiceDesk->formIDs->toArray();

            foreach ($config->ServiceDesk->fields ?? [] as $formName => $fields) {
                foreach (explode(',', $fields) as $field) {
                    list($fieldName, $fieldID) = explode(':', $field);
                    $this->fields[$formName][$fieldName] = $fieldID;
                }
            }
        }
    }

    /**
     * Creates the url to the Service Desk formular. Params can be given to autofill some input fields.
     *
     * @param string $formName Internal form name. Corresponding to config.
     * @param array  $params   Params to autofill. Keys are the internal field names.
     *
     * @return string
     */
    public function __invoke(string $formName, array $params = []) : string {
        if(!$this->enabled) {
            return false;
        }

        $queryParams = [];
        foreach ($params as $fieldName => $value) {
            if($this->fields[$formName][$fieldName] ?? false) {
                $queryParams[$this->fields[$formName][$fieldName]] = $value;
            }
        }

        $paramQuery = !empty($queryParams) ? ("?" . http_build_query($queryParams)) : '';
        return $this->baseUrl . $this->forms[$formName] . $paramQuery;
    }
}
