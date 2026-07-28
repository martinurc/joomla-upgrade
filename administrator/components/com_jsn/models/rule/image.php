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
 * Form Rule class for the Joomla Platform.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormRuleImage extends JFormRule
{
	/**
	 * Method to test an external url for a valid parts.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 * @param   JRegistry         $input    An optional JRegistry object with the entire data set to validate against the entire form.
	 * @param   JForm             $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 *
	 * @since   11.1
	 * @link    http://www.w3.org/Addressing/URL/url-spec.txt
	 * @see	    JString
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, JRegistry $input = null, JForm $form = null)
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string) $element['requiredfile'] == 'true' || (string) $element['requiredfile'] == 'required');
		
		$validate=false;
		
		$jform=JFactory::getApplication()->input->files->get('jform',null,'raw');
		if(isset($jform['upload_'. (string) $element['name']]))$jform_file=$jform['upload_'. (string) $element['name']];
		if(isset($jform_file['name']) && strlen($jform_file['name'])>4)
		{
			$filename=$jform_file['name'];
			$ext = strtolower($filename[strlen($filename)-4].$filename[strlen($filename)-3].$filename[strlen($filename)-2].$filename[strlen($filename)-1]);
			if ($ext[0] == '.') $ext = substr($ext, 1, 3);
			if (!in_array($ext,  explode('|', 'jpg|png|jpeg|gif|bmp') )) return false;
			$validate=true;
		}
		else{
			$form=JFactory::getApplication()->input->get('jform',null,'raw');
			$value=$form[(string) $element['name']];
			$isJson = json_decode($value);
			if(json_last_error() === JSON_ERROR_NONE) $validate=true;
			else{
				if(strpos($value,'?'))
					$filename = JPATH_SITE.'/'.trim(substr($value,0,strpos($value, '?')),'/');
				else
					$filename = JPATH_SITE.'/'.trim($value,'/');
				if(!empty($value) && file_exists($filename)) $validate=true;
			}
		}

		if(!$required) return true;

		if($validate) return true;
		else return false;
	}
}
