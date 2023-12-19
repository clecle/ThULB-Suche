<?php
/**
 * IIIF cover content loader.
 *
 * PHP version 8
 *
 * Copyright (C) ThULB Jena
 *
 * @category VuFind
 * @package  Content
 * @author   Clemens Kynast <clemens.kynast@uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
namespace ThULB\Content\Covers;

class IIIF extends \VuFind\Content\AbstractCover
    implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    protected $config;
    /**
     * Constructor
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->supportsRecordid = $this->cacheAllowed = true;
    }

    /**
     * Get an HTTP client
     *
     * @param string $url URL for client to use
     *
     * @return \Laminas\Http\Client
     */
    protected function getHttpClient($url = null)
    {
        if (null === $this->httpService) {
            throw new \Exception('HTTP service missing.');
        }
        return $this->httpService->createClient($url);
    }

    /**
     * Get image URL for a particular API key and set of IDs (or false if invalid).
     *
     * @param string $key  API key
     * @param string $size Size of image to load (small/medium/large)
     * @param array  $ids  Associative array of identifiers (key should include 'collection_details' as String)
     *
     * @return string|bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($key, $size, $ids)
    {
        if(!($ids['collection_details'] ?? false)) {
            return false;
        }

        $px = match ($size) {
            "small" => $this->config->Content->IIIF_small ?? 200,
            "medium" => $this->config->Content->IIIF_medium ?? 600,
            "large" => $this->config->Content->IIIF_large ?? 1000,
            default => 600,
        };

        // Construct the request URL:
        $collection = $ids['collection_details'];
        $collectionVal = array_shift($collection);
        $url = $this->config->Content->IIIF->$collectionVal;
        $url .= $ids['recordid'] . '/full/!'.$px.','.$px.'/0/default.jpg';
        return $url;
    }
}
