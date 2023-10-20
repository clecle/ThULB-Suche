<?php

namespace ThULB\Content\LocationData;

use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;

class ThULB implements
    HttpServiceAwareInterface, LoggerAwareInterface
{
    use \VuFind\Cache\CacheTrait;
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFindHttp\HttpServiceAwareTrait;

    protected Config $thulbConfig;

    public function __construct($thulbConfig) {
        $this->thulbConfig = $thulbConfig;

        if($thulbConfig->cacheOptions['ttl'] ?? false) {
            $this->cacheLifetime = $thulbConfig->cacheOptions['ttl'];
        }
    }

    public function getLocationData(int $locationID = null) : array {
        $apiUrl = $this->thulbConfig->Location->URL;
        if($locationID !== null) {
            $apiUrl .= '&location=' . $locationID;
        }

        if (isset($this->cache)) {
            if($result = $this->getCacheItem($apiUrl)) {
                return $this->formatResult($result);
            }
        }

        try {
            $response = $this->httpService->get($apiUrl, [], 10);
            if ($response->getStatusCode() == 200) {
                $body = $response->getBody();
                $this->setCacheItem($apiUrl, $body);

                return $this->formatResult($body);
            }
        }
        catch (\Exception $ex) {
            $this->logError($ex);
        }

        return [];
    }

    protected function setCacheItem($cacheKey, $item) : void {
        if ($cacheKey) {
            try {
                $this->putCachedData($cacheKey, $item);
            }
            catch (\Laminas\Cache\Exception\RuntimeException $ex) {
                // Try to determine if caching failed due to response size
                // and log the case accordingly.
                // Unfortunately Laminas Cache does not translate exceptions
                // to any common error codes, so we must check codes and/or
                // message for adapter-specific values.
                // 'ITEM TOO BIG' is the message from the Memcached adapter
                // and comes directly from libmemcached.
                if ($ex->getMessage() === 'ITEM TOO BIG') {
                    $this->debug('Cache setItem failed: ' . $ex->getMessage());
                }
                else {
                    $this->logWarning('Cache setItem failed: ' . $ex->getMessage());
                }
            }
            catch (\Exception $ex) {
                $this->logWarning('Cache setItem failed: ' . $ex->getMessage());
            }
        }
    }

    protected function getCacheItem($cacheKey) : ?string {
        try {
            if ($result = $this->getCachedData($cacheKey)) {
                $this->debug('Returning cached results');
                return $result;
            }
        }
        catch (\Exception $ex) {
            $this->logWarning('Cache getItem failed: ' . $ex->getMessage());
        }

        return null;
    }

    protected function formatResult ($json) : array {
        $result = [];
        foreach (json_decode($json, true) as $location) {
            $tmp = [
                'id' => $location['id'],
                'title' => $location['title'],
                'name' => $location['openinghours']['result']['name'],
                'address' => $location['openinghours']['result']['formatted_address'],
                'phone' => $location['openinghours']['result']['international_phone_number'],
                'googleMapsUrl' => $location['openinghours']['result']['url'],
                'openCloseToday' => $location['openclose'],
                'openingHours' => array (
                    1 => ['day' => 'Monday', 'formattedTime' => 'Closed'],
                    2 => ['day' => 'Tuesday', 'formattedTime' => 'Closed'],
                    3 => ['day' => 'Wednesday', 'formattedTime' => 'Closed'],
                    4 => ['day' => 'Thursday', 'formattedTime' => 'Closed'],
                    5 => ['day' => 'Friday', 'formattedTime' => 'Closed'],
                    6 => ['day' => 'Saturday', 'formattedTime' => 'Closed'],
                    0 => ['day' => 'Sunday', 'formattedTime' => 'Closed'],
                )
            ];

            $weekday = date('N');
            $now = date('Hi');
            // fill opening hours of tmp
            foreach ($location['openinghours']['result']['opening_hours']['periods'] as $period) {
                $jdday = $period['open']['day'];
                $tmp['openingHours'][$jdday] = array_merge (
                    $tmp['openingHours'][$jdday],
                    array (
                        'open' => $period['open']['time'],
                        'close' => $period['close']['time'],
                        'formattedTime' => sprintf(
                            '%02d:%02d - %02d:%02d',
                            $period['open']['time'] / 100,
                            $period['open']['time'] % 100,
                            $period['close']['time'] / 100,
                            $period['close']['time'] % 100
                        )
                    )
                );

                if($weekday % 7 == $jdday) {
                    $tmp['openCloseToday']['nowOpen'] =
                        $period['open']['time'] <= $now && $now < $period['close']['time'];
                }
            }

            $result[] = $tmp;
        }

        return $result;
    }
}