<?php
/**
 * Book Cover Generator
 * mainly for IIIF connectivity on a given index-field & Record-ID
 */
namespace ThULB\Cover;

use VuFind\Content\Covers\PluginManager as ApiManager;
use VuFindCode\ISBN;
use VuFindCode\ISMN;

/**
 * Book Cover Generator
 *
 * @category VuFind
 * @package  Cover_Generator
 * @author   Clemens Kynast <clemens.kynast@uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/configuration:external_content Wiki
 */
class Loader extends \VuFind\Cover\Loader
{
    /**
     * User collection_details parameter
     *
     * @var string
     */
    protected $collection_details;

    /**
     * Get default settings for loadImage().
     *
     * @return array
     */
    protected function getDefaultSettings()
    {
        $settings = parent::getDefaultSettings();
        $settings['collection_details'] = null;

        return $settings;
    }

    /**
     * Support method for loadImage() -- sanitize and store some key values.
     *
     * @param array $settings Settings from loadImage
     *
     * @return void
     */
    protected function storeSanitizedSettings($settings)
    {
        parent::storeSanitizedSettings($settings);
        $this->collection_details = $settings['collection_details'];
    }

    /**
     * Support method for fetchFromAPI() -- set the localFile property.
     *
     * @param array $ids IDs returned by getIdentifiers() method
     *
     * @return string
     */
    protected function determineLocalFile($ids)
    {
        if (isset($ids['recordid']) && isset($ids['source'])) {
            return $this->getCachePath(
                $this->size,
                'ID' . md5($ids['source'] . '|' . $ids['recordid'])
            );
        } elseif (isset($ids['collection_details'])) {
            return $this->getCachePath($this->size, 'IIIF' . $ids['collection_details']);
        }
        throw new \Exception('Cannot determine local file path.');
    }

    /**
     * Get all valid identifiers as an associative array.
     *
     * @return array
     */
    protected function getIdentifiers()
    {
        $ids = parent::getIdentifiers();
        if ($this->collection_details) {
            $ids['collection_details'] = $this->collection_details;
        }
        return $ids;
    }
}
