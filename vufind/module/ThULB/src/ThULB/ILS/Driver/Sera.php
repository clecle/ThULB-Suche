<?php

namespace ThULB\ILS\Driver;

use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\ILS\Driver\AbstractBase;
use VuFind\Log\LoggerAwareTrait;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

class Sera extends AbstractBase implements
    HttpServiceAwareInterface, LoggerAwareInterface, TranslatorAwareInterface
{
    use HttpServiceAwareTrait;
    use LoggerAwareTrait;
    use TranslatorAwareTrait;

    protected Config $thulbConfig;

    public function __construct(Config $thulbConfig) {
        $this->thulbConfig = $thulbConfig;
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
        $postData = "SELECT orderstatus_code FROM orders o" .
                        " WHERE o.iln = 31 AND o.orderstatus_code IN ('t', 'b')" .
                        " AND o.epn = " . $id;

        $response = $this->sendRequest($postData);

        return $response['result'][0] ?? [];
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
     */
    public function getHolding($id, array $patron = null, array $options = []) : array {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id) : array {
        return [];
    }

    protected function sendRequest(string $postData) : mixed {
        if(!$this->thulbConfig->SERA) {
            return [];
        }

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->thulbConfig->SERA->Token
        ];

        $response =  $this->httpService->post(
            $this->thulbConfig->SERA->URL,
            'sql=' . $postData,
            \Laminas\Http\Client::ENC_URLENCODED,
            10000,
            $headers
        );

        return json_decode($response->getBody(), true);
    }

    protected function getAddressIdNr(string $username) : int|bool {
        $sql = "SELECT * FROM borrower WHERE borrower_bar='$username'";
        $response = $this->sendRequest($sql);

        return $response['result'][0]['address_id_nr'] ?? false;
    }

    protected function getLastRequisitionIDNumber() : int|bool {
        $sql = "SELECT TOP 1 id_number FROM requisition ORDER BY id_number DESC";
        $response = $this->sendRequest($sql);

        return $response['result'][0]['id_number'] ?? false;
    }

    protected function insertRequisition(array $data) : bool {
        $sql =
            "INSERT INTO requisition " .
                "(" . implode(', ', array_keys($data)) . ") " .
            "VALUES " .
                "(" . implode(', ', $data) . ") ";

        $this->sendRequest($sql);

        return $this->getLastRequisitionIDNumber() == $data['id_number'];
    }

    public function chargeIllFee(string $username, $quantity, $cost) : bool {
        try {
            if(getenv('VUFIND_ENV') != 'production') {
                // there is no SERA test API, use a live user
                $username = $this->thulbConfig->SERA->testLiveUser;
            }

            // get address_id_nr of the user
            $addressIdNr = $this->getAddressIdNr($username);

            // get last requisition id
            $lastRequisitionIDNumber = $this->getLastRequisitionIDNumber();

            $dateTime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            $extraInformation = $this->translate(
                'ill_requisition_extra_information', [
                    '%%date%%' => (new \DateTimeImmutable())->format('d.m.Y'),
                    '%%quantity%%' => $quantity
                ]
            );

            return $this->insertRequisition(array(
                'iln' => 31,
                'department_group_nr' => 1,
                'address_id_nr' => $addressIdNr,
                'id_number' => ++$lastRequisitionIDNumber,
                'costs_code' => 15,
                'costs' => $cost,
                'extra_information' => "'$extraInformation'",
                'date_of_creation' => "'$dateTime'",
                'edit_date' => "'$dateTime'",
            ));
        }
        catch (\Exception $e) {
            $this->logError($e);
        }

        return false;
    }
}
