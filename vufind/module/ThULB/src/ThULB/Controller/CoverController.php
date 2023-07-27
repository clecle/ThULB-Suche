<?php
/**
 * Cover Controller ThULB
 * mainly for IIIF connectivity on a given index-field & Record-ID
 */
namespace ThULB\Controller;

use VuFind\Cover\CachingProxy;
use VuFind\Cover\Loader;
use VuFind\Session\Settings as SessionSettings;

/**
 * Generates covers for book entries
 *
 * @category VuFind
 * @package  Controller
 * @author   Clemens Kynast <clemens.kynast@uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class CoverController extends \VuFind\Controller\CoverController
{
    /**
     * Convert image parameters into an array for use by the image loader.
     *
     * @return array
     */
    protected function getImageParams()
    {
        $params = parent::getImageParams();
        $params['collection_details'] = $this->params()->fromQuery('collection_details');
        return $params;
    }

}
