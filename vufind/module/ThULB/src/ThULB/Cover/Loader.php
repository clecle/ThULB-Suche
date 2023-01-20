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
        $this->collection_details = $settings['collection_details'] ?? null;
    }

    /**
     * Support method for fetchFromAPI() -- set the localFile property.
     *
     * @param array $ids IDs returned by getIdentifiers() method
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function determineLocalFile($ids)
    {
        // We should check whether we have cached images for the 13- or 10-digit
        // ISBNs. If no file exists, we'll favor the 10-digit number if
        // available for the sake of brevity.
        if (isset($ids['isbn'])) {
            $file = $this->getCachePath($this->size, $ids['isbn']->get13());
            if (!is_readable($file) && $ids['isbn']->get10()) {
                return $this->getCachePath($this->size, $ids['isbn']->get10());
            }
            return $file;
        } elseif (isset($ids['issn'])) {
            return $this->getCachePath($this->size, $ids['issn']);
        } elseif (isset($ids['oclc'])) {
            return $this->getCachePath($this->size, 'OCLC' . $ids['oclc']);
        } elseif (isset($ids['upc'])) {
            return $this->getCachePath($this->size, 'UPC' . $ids['upc']);
        } elseif (isset($ids['nbn'])) {
            return $this->getCachePath($this->size, 'NBN' . $ids['nbn']);
        } elseif (isset($ids['ismn'])) {
            return $this->getCachePath($this->size, 'ISMN' . $ids['ismn']->get13());
        } elseif (isset($ids['uuid'])) {
            return $this->getCachePath($this->size, 'UUID' . $ids['uuid']);
        } elseif (isset($ids['recordid']) && isset($ids['source'])) {
            return $this->getCachePath(
                $this->size,
                'ID' . md5($ids['source'] . '|' . $ids['recordid'])
            );
        } elseif (isset($ids['collection_details']) && isset($ids['recordid'])) {
            return $this->getCachePath($this->size, 'IIIF_' . $ids['collection_details'] . '_' . $ids['recordid']);
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
