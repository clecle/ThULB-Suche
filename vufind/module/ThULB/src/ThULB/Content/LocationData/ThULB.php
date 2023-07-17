<?php

namespace ThULB\Content\LocationData;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;

class ThULB implements
    HttpServiceAwareInterface, LoggerAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

    protected Config $thulbConfig;
    protected StorageInterface $cache;

    public function __construct($thulbConfig) {
        $this->thulbConfig = $thulbConfig;
    }

    public function setCache(StorageInterface $cache) : void {
        $this->cache = $cache;
    }

    public function getLocationData(int $locationID = null) : array {
        $apiUrl = $this->thulbConfig->Location->URL;
        if($locationID !== null) {
            $apiUrl .= '&location=' . $locationID;
        }

        $cacheKey = null;
        if (isset($this->cache)) {
            $cacheKey = md5($apiUrl);
            if($result = $this->getCacheItem($cacheKey)) {
                return $result;
            }
        }

        try {
            $result = $this->httpService->get($apiUrl)->getBody();
            $this->setCacheItem($cacheKey, $result);

            return $this->formatResult($result);
        }
        catch (\Exception $ex) {
            $this->logError($ex);
        }

        return [];
    }

    protected function setCacheItem($cacheKey, $item) {
        if ($cacheKey) {
            try {
                $this->cache->setItem($cacheKey, $item);
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

    protected function getCacheItem($cacheKey) : ?array {
        try {
            if ($result = $this->cache->getItem($cacheKey)) {
                $this->debug('Returning cached results');
                return $this->formatResult($result);
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

            // fill opening hours of tmp
            foreach ($location['openinghours']['result']['opening_hours']['periods'] as $period) {
                $tmp['openingHours'][$period['open']['day']] = array_merge(
                    $tmp['openingHours'][$period['open']['day']],
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
            }

            $result[] = $tmp;
        }

        return $result;
    }
}