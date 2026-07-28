<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package     Joomla.Libraries
 * @subpackage  Form
 * @since       3.1
 */
class JFormFieldTypes extends JFormFieldList
{
	public $type = 'Types';
	
	protected $comParams = null;

	/**
	 * Constructor
	 *
	 * @since  3.1
	 */
	public function __construct()
	{
		parent::__construct();

		// Load com_jsn config
		$this->comParams = JComponentHelper::getParams('com_jsn');
	}

	
	protected function getOptions()
	{
		// Include Field Class
		foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
			require_once $filename;
		}
		
		global $_FIELDTYPES;
		$options = $_FIELDTYPES;

		// Translate Options Text
		foreach($options as &$value){
			$value=JText::_($value);
		}

		// Merge any additional options in the XML definition.
		if (!isset($this->element['level'])) $options = array_merge(parent::getOptions(), $options);

		return $options;
	}

}
