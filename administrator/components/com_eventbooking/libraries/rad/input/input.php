<?php
/**
 * @package     RAD
 * @subpackage  Input
 *
 * @copyright   Copyright (C) 2015 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
define('RAD_INPUT_ALLOWRAW', 2);
define('RAD_INPUT_ALLOWHTML', 4);

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Input\Input;

/**
 * Extends JInput class to allow getting raw data from Input object. This can be removed when we don't provide support for Joomla 2.5.x
 *
 * @package       RAD
 * @subpackage    Input
 * @since         1.0
 *
 * @property-read    RADInput $get
 * @property-read    RADInput $post
 */
class RADInput extends Input
{
	/**
	 * Keep a reference of original input object
	 *
	 * @var \Joomla\CMS\Input\Input
	 */
	protected $input;

	/**
	 * Constructor.
	 *
	 * @param   array  $source   Source data (Optional, default is $_REQUEST)
	 * @param   array  $options  Array of configuration parameters (Optional)
	 */
	public function __construct($source = null, array $options = [])
	{
		if ($source instanceof Input)
		{
			$this->input = $source;

			$reflection = new ReflectionClass($source);
			$property   = $reflection->getProperty('data');
			$property->setAccessible(true);
			$source = $property->getValue($source);
		}

		if (!isset($options['filter']))
		{
			if (version_compare(JVERSION, '4.0.0', 'ge'))
			{
				//Set default filter so that getHtml can be returned properly
				$options['filter'] = InputFilter::getInstance([], [], 1, 1);
			}
			else
			{
				$options['filter'] = InputFilter::getInstance(null, null, 1, 1);
			}
		}

		parent::__construct($source, $options);
	}

	/**
	 * Get data from the input
	 *
	 * @param   int  $mask
	 *
	 * @return mixed
	 */
	public function getData($mask = RAD_INPUT_ALLOWHTML)
	{
		if ($mask & 2)
		{
			return $this->data;
		}

		return $this->filter->clean($this->data, '');
	}

	/**
	 * Set data for the input object. This is usually called when you get data, modify it, and then set it back
	 *
	 * @param $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Magic method to get an input object
	 *
	 * @param   mixed  $name  Name of the input object to retrieve.
	 *
	 * @return \Joomla\CMS\Input\Input The request input object
	 *
	 * @since   11.1
	 */
	public function __get($name)
	{
		if (isset($this->inputs[$name]))
		{
			return $this->inputs[$name];
		}

		$className = '\\Joomla\\CMS\\Input\\' . ucfirst($name);

		if (class_exists($className))
		{
			$this->inputs[$name] = new $className(null, $this->options);

			return $this->inputs[$name];
		}

		$superGlobal = '_' . strtoupper($name);

		if (isset($GLOBALS[$superGlobal]))
		{
			$this->inputs[$name] = new RADInput($GLOBALS[$superGlobal], $this->options);

			return $this->inputs[$name];
		}
	}

	/**
	 * Check to see if a variable is available in the input or not
	 *
	 * @param   string  $name  the variable name
	 *
	 * @return boolean
	 */
	public function has($name)
	{
		return $this->exists($name);
	}

	/**
	 * Check if a variable is available in input
	 *
	 * @param   string  $name
	 *
	 * @return bool
	 */
	public function exists($name)
	{
		if (isset($this->data[$name]))
		{
			return true;
		}

		return false;
	}

	/**
	 * Remove a variable from input
	 *
	 * @param   string  $name
	 */
	public function remove($name)
	{
		unset($this->data[$name]);
	}

	/**
	 * Override set method to make change back to Joomla Input Object
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 */
	public function set($name, $value)
	{
		parent::set($name, $value);

		// Store change back to the original Joomla Input object if provided
		if ($this->input)
		{
			$this->input->set($name, $value);
		}
	}
}
