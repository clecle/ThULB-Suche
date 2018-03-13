<?php
/**
 * Queried cookie permission provider for VuFind (forked from Vufind\Role\ServerParam).
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @package  Authorization
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Bernd Oberknapp <bo@ub.uni-freiburg.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace ThULB\Role\PermissionProvider;
use VuFind\Role\PermissionProvider\PermissionProviderInterface;
use Vufind\Cookie\CookieManager;
use Zend\Http\PhpEnvironment\Request;

/**
 * Queried cookie permission provider for VuFind.
 *
 * @category ThULB
 * @package  Authorization
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Bernd Oberknapp <bo@ub.uni-freiburg.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class QueriedCookie implements PermissionProviderInterface,
    \Zend\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Request object
     *
     * @var Request
     */
    protected $request;
    
    /**
     * The CookieManager
     *
     * @var CookieManager
     */
    protected $cookieManager;
    
    /**
     * Lifetime of a cookie to keep the information about a provided query param.
     *
     * @var type 
     */
    protected $cookieLifetime = 31536000;   // one year

    /**
     * Aliases for query param names (default: none)
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Delimiter for multi-valued query params (default: none)
     *
     * @var string
     */
    protected $queryParamDelimiter = '';

    /**
     * Escape character for delimiter in query param strings (default: none)
     *
     * @var string
     */
    protected $queryParamEscape = '';

    /**
     * Constructor
     *
     * @param Request $request Request object
     */
    public function __construct(Request $request, CookieManager $cookieManager)
    {
        $this->request = $request;
        $this->cookieManager = $cookieManager;
    }

    /**
     * Return an array of roles which may be granted the permission based on
     * the options.
     *
     * @param mixed $options Options provided from configuration.
     *
     * @return array
     */
    public function getPermissions($options)
    {
        // user only gets the permission if all options match (AND)
        foreach ((array)$options as $option) {
            $this->debug("getPermissions: option '{$option}'");
            if (!$this->checkGetParam($option)) {
                $this->debug("getPermissions: result = false");
                return [];
            }
            $this->debug("getPermissions: result = true");
        }
        return ['guest', 'loggedin'];
    }

    /**
     * Check if a query param matches the option.
     *
     * @param string $option Option
     *
     * @return bool true if a get param matches, false if not
     */
    protected function checkGetParam($option)
    {
        // split option on spaces unless escaped with backslash
        $optionParts = $this->splitString($option, ' ', '\\');
        if (count($optionParts) < 2) {
            $this->logError("configuration option '{$option}' invalid");
            return false;
        }

        // first part is the query param name
        $queryParamName = array_shift($optionParts);
        if (isset($this->aliases[$queryParamName])) {
            $queryParamName = $this->aliases[$queryParamName];
        }

        // optional modifier follow query param name
        $modifierMatch = in_array($optionParts[0], ['~', '!~']);
        $modifierNot = in_array($optionParts[0], ['!', '!~']);
        if ($modifierNot || $modifierMatch) {
            array_shift($optionParts);
        }

        // remaining parts are the templates for checking the query params
        $templates = $optionParts;
        if (empty($templates)) {
            $this->logError("configuration option '{$option}' invalid");
            return false;
        }

        // query param values to check
        $queryParamString = ($this->request->getQuery()->get($queryParamName)) ?: $this->cookieManager->get($queryParamName);
        if ($queryParamString === null) {
            // check fails if query param is missing
            return false;
        }
        $queryParams = $this->splitString(
            $queryParamString, $this->queryParamDelimiter, $this->queryParamEscape
        );

        $result = false;
        // check for each query param ...
        foreach ($queryParams as $queryParam) {
            // ... if it matches one of the templates (OR)
            foreach ($templates as $template) {
                if ($modifierMatch) {
                    $result |= preg_match('/' . $template . '/', $queryParam);
                } else {
                    $result |= ($template === $queryParam);
                }
            }
        }
        if ($modifierNot) {
            $result = !$result;
        }

        $this->cookieManager->set($queryParamName, $queryParamString, time() + $this->cookieLifetime);
        
        return $result;
    }

    /**
     * Split string on delimiter unless dequalified with escape
     *
     * @param string $string    String to split
     * @param string $delimiter Delimiter character
     * @param string $escape    Escape character
     *
     * @return array split string parts
     */
    protected function splitString($string, $delimiter, $escape)
    {
        if ($delimiter === '') {
            return [$string];
        }

        if ($delimiter === ' ') {
            $pattern = ' +';
        } else {
            $pattern = preg_quote($delimiter, '/');
        }

        if ($escape === '') {
            $pattern = '(?<!' . preg_quote($escape, '/') . ')' . $pattern;
        }

        return str_replace(
            $escape . $delimiter, $delimiter,
            preg_split('/' . $pattern . '/', $string)
        );
    }
}
