<?php

namespace ThULB\ILS\Driver;

use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;
use ThULB\ILS\Driver\Exception\UnauthorizedException;
use ThULB\Log\LoggerAwareTrait;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\ILS\Driver\AbstractBase;
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

        try {
            $response = $this->sendRequest($postData);
        }
        catch (\Exception $e) {
            $this->logException($e);
        }

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

    /**
     * Sends request data to Sera API.
     *
     * @param string $sql
     *
     * @return mixed
     *
     * @throws UnauthorizedException
     */
    protected function sendRequest(string $sql) : mixed {
        if(!$this->thulbConfig->SERA) {
            return [];
        }

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->thulbConfig->SERA->Token
        ];

        $response =  $this->httpService->post(
            $this->thulbConfig->SERA->URL,
            'sql=' . $sql,
            \Laminas\Http\Client::ENC_URLENCODED,
            10000,
            $headers
        );

        if($response->getStatusCode() == 401) {
            throw new UnauthorizedException('Unauthorized Request to ' . $this->thulbConfig->SERA->URL);
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * Adds the new requisition.
     *
     * @param string $username
     * @param array $data
     *
     * @return int|false
     *
     * @throws UnauthorizedException
     */
    protected function insertRequisition(string $username, array $data) : int|false {
        $data += [
            'address_id_nr' => '@local_addr_id_nr',
            'id_number' => '@local_req_id_nr',
            'date_of_creation' => "getdate()",
            'edit_date' => "getdate()",
        ];

        $insertKeys = implode(', ', array_keys($data));
        $insertData = implode(', ', $data);

        $iln = $data['iln'];
        $sql = <<<SQL
            DECLARE @local_addr_id_nr INT
            SELECT @local_addr_id_nr = address_id_nr FROM borrower WHERE borrower_bar = '{$username}' AND iln = {$iln}
            
            DECLARE @local_req_id_nr INT
            SELECT TOP 1 @local_req_id_nr = id_number + 1 FROM requisition ORDER BY id_number DESC
             
            INSERT INTO requisition
                ($insertKeys)
            VALUES
                ($insertData)
            
            SELECT * FROM requisition WHERE id_number = @local_req_id_nr
        SQL;

        $response = $this->sendRequest(urlencode($sql));

        return $response['result'][0]['id_number'] ?? false;
    }

    /**
     *
     *
     * @param string $username
     * @param string $quantity
     * @param string $cost
     *
     * @return int|false
     */
    public function chargeIllFee(string $username, string $quantity, string $cost) : int|false {
        try {
            if(getenv('VUFIND_ENV') != 'production') {
                // there is no SERA test API, use a live user
                $username = $this->thulbConfig->SERA->testLiveUser;
            }

            $extraInformation = $this->translate(
                'ill_requisition_extra_information', [
                    '%%date%%' => (new \DateTimeImmutable())->format('d.m.Y'),
                    '%%quantity%%' => $quantity
                ]
            );

            return $this->insertRequisition($username, array(
                'iln' => 31,
                'department_group_nr' => 1,
                'costs_code' => 15,
                'costs' => $cost,
                'extra_information' => "'$extraInformation'",
            ));
        }
        catch (\Exception $e) {
            $this->logException($e);
        }

        return false;
    }
}
