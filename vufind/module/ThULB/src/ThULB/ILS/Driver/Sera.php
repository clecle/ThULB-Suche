<?php

namespace ThULB\ILS\Driver;

use http\Client;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;
use VuFind\ILS\Driver\AbstractBase;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;

class Sera extends AbstractBase implements
    HttpServiceAwareInterface, LoggerAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

    protected $seraConfig;

    public function __construct(Config $seraConfig) {
        $this->seraConfig = $seraConfig;
    }

    public function init() {}

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the status for
     *
     * @return array
     */
    public function getStatus($id) : array {
        $postData = "sql=SELECT convert(char(10),d.expected_date,104) AS expected_date, (SELECT description FROM orderstatus WHERE iln=31 AND orderstatus_code = o.orderstatus_code) AS order_status" .
                     " FROM orders o, delivery d WHERE o.iln = 31 AND d.iln=31 AND o.order_id_nr = d.order_id_nr AND o.orderstatus_code not in ('u', 'v', 'w')" .
                     " AND o.epn = " . $id;

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->seraConfig->API->Token
        ];

        $response = $this->httpService->post(
            $this->seraConfig->API->URL,
            $postData,
            \Laminas\Http\Client::ENC_URLENCODED,
            10000,
            $headers
        );

        return json_decode($response->getBody(), true)['result'][0] ?? [];
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return array    An array of status information values on success.
     */
    public function getStatuses($ids) : array {
        $results = [];
        foreach($ids as $epn) {
            $results[$epn] = $this->getStatus($epn);
        }

        return $results;
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string     $id      The record id to retrieve the holdings for
     * @param array|null $patron  Patron data
     * @param array      $options Extra options (not currently used)
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getHolding($id, array $patron = null, array $options = []) : array {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id) : array {
        return [];
    }
}