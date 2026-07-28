<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('JPATH_PLATFORM') or die;

/**
 * Form Rule class for the Joomla Platform
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormRuleNumeric extends JFormRule
{
	
	public function test(SimpleXMLElement $element, $value, $group = null, JRegistry $input = null, JForm $form = null)
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string) $element['required'] == 'true' || (string) $element['required'] == 'required');
		if (!$required && empty($value))
		{
			return true;
		}
		
		if(!is_numeric($value)){
			return false;
		}	
		if ($value>$element['max'])
		{
			return false;
		}
		if ($value<$element['min'])
		{
			return false;
		}

		return true;
	}
}
