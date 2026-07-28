<?php
/**
 * @name		CK Object
 * @copyright	Copyright (C) 2025. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @author		Cedric Keiflin - https://www.template-creator.com - https://www.joomlack.fr
 */

/*
 * NOTES : use getData to store in the session, example : setUserState(self::$prefix . '.' . self::$name, $this->state->getData());
 * this will store the array of values instead of the CKObject
 */

namespace Cookiesck;

// No direct access
defined('_JEXEC') or die('Restricted access');

class CKObject {

	protected static $instance;

	protected $vars = array();

	public function __construct($data = []) {
		$this->vars = $data;
	}

	static function getInstance() {

		if (is_object(self::$instance))
		{
			return self::$instance;
		}

		// Instantiate the class, store it to the static container, and return it
		return self::$instance = new CKObject();
	}

	public function get($var, $default = null, $filter = '') {
		// clean the variable name
		// $var = preg_replace('/[^A-Z0-9_\.-]/i', '', $var);

		// B/C we need this to migrate from the CMSObject used before Joomla 6
		if (! is_array($this->vars)) $this->vars = (array)$this->vars;

		if (isset($this->vars[$var])) {
			return $this->vars[$var];
		} else {
			$this->vars[$var] = $default;
		}

		return $this->vars[$var];
	}

	/**
	 ** Get filter according to PHP doc : https://www.php.net/manual/en/filter.filters.sanitize.php
	 **/
	private function filter($var, $filter) {

		switch($filter) {
			case 'default' :
			case 'string' :
			default :
				return filter_var($var, FILTER_UNSAFE_RAW);
				break;
			case 'raw' :
				return ($var);
				break;
			case 'int' :
				return (int)filter_var($var, FILTER_SANITIZE_NUMBER_INT);
				break;
			case 'email' :
				return filter_var($var, FILTER_SANITIZE_EMAIL);
				break;
			case 'url' :
				return filter_var($var, FILTER_SANITIZE_URL);
				break;
			case 'array' :
				return filter_var_array($var, FILTER_DEFAULT);
				break;
		}
	}

	public function set($var, $value = '') {
		// clean the variable name
		// $var = preg_replace('/[^A-Z0-9_\.-]/i', '', $var);

		// B/C we need this to migrate from the CMSObject used before Joomla 6
		if (! is_array($this->vars)) $this->vars = (array)$this->vars;

		$this->vars[$var] = $value;
	}

	/**
	 * Gets an array of values from the request.1
	 */
	public function getArray(array $vars = array(), $datasource = null)
	{
		return filter_var_array($vars, FILTER_DEFAULT);
	}

	/**
	 * Return the data as simple array to store in the session
	 * 
	 * @return array	The list of name - value pairs
	 */
	public function getData() {
		return $this->vars;
	}
}
