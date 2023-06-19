<?php

namespace ThULB\ILS\Driver;

use Laminas\Config\Config;
use Laminas\Http\Response;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\ILS\Driver\AbstractBase;
use VuFind\Log\LoggerAwareTrait;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;
use Whoops\Exception\ErrorException;

class CBSUserdpo extends AbstractBase implements
    HttpServiceAwareInterface, LoggerAwareInterface, TranslatorAwareInterface
{
    use HttpServiceAwareTrait;
    use LoggerAwareTrait;
    use TranslatorAwareTrait;

    protected Config $thulbConfig;

    protected array $userInformation = [];

    public function __construct(Config $thulbConfig) {
        $this->thulbConfig = $thulbConfig;
    }

    /**
     * Get the user information from the API.
     *
     * @param string $username
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function getUserInformation(string $username) : array {
        if(!$this->userInformation) {
            $headers = [
                'Accept' => '*/*',
                'Authorization' => 'apikey ' . $this->thulbConfig->ILL->pass,
            ];

            $response = $this->httpService->get(
                $this->thulbConfig->ILL->host . '/users/' . $username . '/deposit',
                [],
                10000,
                $headers
            );

            return $this->parseAndValidateResponse($response, $username);
        }

        return $this->userInformation[$username];
    }

    /**
     * Charge credits for the user.
     *
     * @param string $username
     * @param string|int $quantity
     *
     * @return array
     *
     * @throws ErrorException
     */
    public function chargeCredits(string $username, string|int $quantity) : array {
        $headers = [
            'Accept' => '*/*',
            'Authorization' => 'apikey ' . $this->thulbConfig->ILL->pass,
            'Content-Type' => 'application/json',
        ];

        $response =  $this->httpService->post(
            $this->thulbConfig->ILL->host . '/users/' . $username . '/deposit',
            json_encode(['paidAmount' => (int) $quantity * 100]),
            \Laminas\Http\Client::ENC_URLENCODED,
            10000,
            $headers
        );

        return $this->parseAndValidateResponse($response, $username);
    }

    /**
     * Parses the response and throws an error if one occurred in the API.
     *
     * @param Response $response
     * @param string   $username
     *
     * @return array
     *
     * @throws ErrorException
     */
    protected function parseAndValidateResponse(Response $response, string $username) : array {
        $json = json_decode($response->getBody(), true);

        if($json['errors'] ?? false) {
            $errorMsg = match ($json['errors'][0]['status']) {
                '400' => 'Failed to parse JSON request body',
                '401' => 'Authentication missing or failed',
                '403' => 'CBS user has insufficient privileges',
                '404' => 'The record could not be found',
                '422' => 'Missing or malformed parameter',
                default => 'An error occured.',
            };

            throw new ErrorException($errorMsg, $json['errors'][0]['status']);
        }

        if($json['data'] ?? false) {
            $json['data']['balance'] /= 100;
        }

        return $this->userInformation[$username] = $json['data'];
    }

    public function init() {}

    public function getStatus($id) : array {
        // not supportet
        return [];
    }

    public function getStatuses($ids) : array {
        // not supportet
        return [];
    }

    public function getHolding($id, array $patron = null, array $options = []) : array {
        // not supportet
        return [];
    }

    public function getPurchaseHistory($id) : array {
        // not supportet
        return [];
    }
}
