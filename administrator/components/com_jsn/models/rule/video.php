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
class JFormRuleVideo extends JFormRule
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
		$required = ((string) $element['required'] == 'true' || (string) $element['required'] == 'required');
		if (!$required && empty($value))
		{
			return true;
		}
		$urlParts = JString::parse_url($value);

		// See http://www.w3.org/Addressing/URL/url-spec.txt
		// Use the full list or optionally specify a list of permitted schemes.
		if ($element['schemes'] == '')
		{
			$scheme = array('http', 'https', 'ftp', 'ftps', 'gopher', 'mailto', 'news', 'prospero', 'telnet', 'rlogin', 'tn3270', 'wais', 'url',
				'mid', 'cid', 'nntp', 'tel', 'urn', 'ldap', 'file', 'fax', 'modem', 'git');
		}
		else
		{
			$scheme = explode(',', $element['schemes']);
		}

		/*
		 * This rule is only for full URLs with schemes because parse_url does not parse
		 * accurately without a scheme.
		 * @see http://php.net/manual/en/function.parse-url.php
		 */
		if ($urlParts && !array_key_exists('scheme', $urlParts))
		{
			return false;
		}
		$urlScheme = (string) $urlParts['scheme'];
		$urlScheme = strtolower($urlScheme);
		if (in_array($urlScheme, $scheme) == false)
		{
			return false;
		}
		// For some schemes here must be two slashes.
		if (($urlScheme == 'http' || $urlScheme == 'https' || $urlScheme == 'ftp' || $urlScheme == 'sftp' || $urlScheme == 'gopher'
			|| $urlScheme == 'wais' || $urlScheme == 'gopher' || $urlScheme == 'prospero' || $urlScheme == 'telnet' || $urlScheme == 'git')
			&& ((substr($value, strlen($urlScheme), 3)) !== '://'))
		{
			return false;
		}
		// The best we can do for the rest is make sure that the strings are valid UTF-8
		// and the port is an integer.
		if (array_key_exists('host', $urlParts) && !JString::valid((string) $urlParts['host']))
		{
			return false;
		}
		if (array_key_exists('port', $urlParts) && !is_int((int) $urlParts['port']))
		{
			return false;
		}
		if (array_key_exists('path', $urlParts) && !JString::valid((string) $urlParts['path']))
		{
			return false;
		}
		// Video
		if(!(strpos(' '.$value,'https://www.youtube.com/watch?v=')==1 || strpos(' '.$value,'http://www.youtube.com/watch?v=')==1 || strpos(' '.$value,'https://m.youtube.com/watch?v=')==1 || strpos(' '.$value,'http://www.youtube.com/watch?v=')==1 || strpos(' '.$value,'http://youtu.be/')==1 || strpos(' '.$value,'http://vimeo.com/')==1 || strpos(' '.$value,'https://vimeo.com/')==1)) return false;
		return true;
	}
}
