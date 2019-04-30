<?php


namespace ThULB\Controller;


use Exception;
use VuFind\Controller\AbstractBase;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class DynMessagesController extends AbstractBase
{
    use \VuFind\Log\LoggerAwareTrait;

    private $_basePath;
    private $_iniGerman;
    private $_iniEnglish;
    private $_languageCache;
    private $_tags;


    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);

        $this->setLogger($sm->get('VuFind\Logger'));

        $this->accessPermission = 'access.DynamicMessages';

        // File paths
        $this->_basePath = APPLICATION_PATH . '/local_thulb';
        $this->_iniGerman = $this->_basePath . '/languages/dynMessages_de.ini';
        $this->_iniEnglish = $this->_basePath . '/languages/dynMessages_en.ini';
        $this->_languageCache = $this->_basePath . '/cache/languages';

        // possible text fields
        $this->_tags = array(
            'message_under_search_box' => 'Text für eine Hinweisbox, die unter dem Suchfeld angezeigt wird,'
        );
    }

    public function homeAction()
    {
        $german = $this->readLanguageFile($this->_iniGerman);
        $english = $this->readLanguageFile($this->_iniEnglish);

        $debug = array(
            'REMOTE_ADDR' => $this->getRequest()->getServer()->get('REMOTE_ADDR'),
            'hideVpnWarning' => $this->serviceLocator->get('VuFind\Role\PermissionManager')->isAuthorized('hide.VpnWarning')
        );
        echo '<pre>' . print_r($debug, true) . '</pre>';

        return new ViewModel(array(
                'german' => $german,
                'english' => $english,
                'tags' => $this->_tags,
            ));
    }

    public function saveAction()
    {
        $english = $this->params()->fromPost('english');
        $german = $this->params()->fromPost('german');

        if(!empty($english) && !empty($german)) {
            if ($this->writeLanguageFile($this->_iniGerman, $german)
                && $this->writeLanguageFile($this->_iniEnglish, $english)
                && $this->delTree($this->_languageCache)) {

                $this->flashMessenger()->addSuccessMessage('Die Eingaben wurden erfolgreich gespeichert.');
            } else {
                $this->flashMessenger()->addErrorMessage('Die Eingaben konnten nicht gespeichert werden.');
            }
        }

        return $this->redirect()->toUrl('/dynMessages');
    }

    /**
     * Reads the given file and returns a associative array.
     * Values in the file are separated by a '='.
     *
     * @param String $fileName Name of the file to be read.
     *
     * @return array
     */
    function readLanguageFile($fileName) {

        $values = array();

        if(is_file($fileName) && is_readable($fileName)) {
            try{
                $file = fopen($fileName, 'r');

                while (($line = fgets($file)) !== false) {
                    $line = trim($line);

                    $pos = strpos($line, '=');
                    $tag = substr($line, 0, $pos);
                    $text = substr($line, $pos + 1);

                    $tag = trim($tag);
                    $text = trim($text);
                    $text = str_replace('<br>', "\n", $text);

                    $values[$tag] = substr($text, 1, strlen($text) - 2);
                }
            }
            finally {
                if($file !== false) {
                    fclose($file);
                }
            }
        }
        else {
            $this->flashMessenger()->addErrorMessage("Die Datei '$fileName' konnte nicht gelesen werden.<br>");
        }

        return $values;
    }

    /**
     * Writes the given values into the specified file.
     * Values in the file are separated by a '='.
     *
     * @param String $fileName  Name of the file to be written.
     * @param array  $values    Values to be written.
     *
     * @return boolean  Returns TRUE if files could be written, FALSE otherwise.
     */
    function writeLanguageFile($fileName, $values) {

        $success = true;
        if(is_file($fileName) && is_writable($fileName)) {
            ksort($values);

            try{
                $file = fopen($fileName, 'w');
                foreach ($values as $tag => $text) {
                    $tag = trim($tag);
                    $text = strip_tags($text, '<a><i>');
                    $text = trim($text);
                    $text = str_replace(["\r\n", "\n"], '<br>', $text);
                    $line = "$tag = \"$text\"\n";

                    fwrite($file, $line);
                }
            }
            catch (Exception $e) {
                $success = false;
            }
            finally {
                if($file !== false) {
                    fclose($file);
                }
            }        }
        else {
            $this->flashMessenger()->addErrorMessage("Die Datei '$fileName' konnte nicht geschrieben werden.<br>");
        }

        return $success;
    }

    /**
     * Deletes a directory with its content.
     *
     * @param String $dir Path of the directory to delete
     *
     * @return bool Returns TRUE if the directory does not exist OR if the directory was successfully deleted.
     */
    function delTree($dir) {
        if(!file_exists($dir)) {
            return true;
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}