<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;




/**
 * Form Field class for the Joomla Platform.
 * Provides spacer markup to be used in form layouts.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldDelimeter extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Delimeter';

	/**
	 * Method to get the field input markup for a spacer.
	 * The spacer does not have accept input.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$html = array();
		$class = $this->element['class'] ? (string) $this->element['class'] : '';
		$text = $this->element['text'] ? (string) $this->element['text'] : (string) $this->element['label'];
		//if(JText::_(strip_tags($text)) != strip_tags($text)) $text=JText::_(strip_tags($text));
		if(substr_count($text,' ')==0) $text=JText::_(strip_tags($text));
		
		$html[] = '<div id="'.$this->id.'" >';
		$html[] = JHtml::_('content.prepare', $text, '', 'jsn_content.content');
		$html[] = '</div>';
		
		return implode('', $html);
		
	}

	/*protected function getLabel()
	{
		return '<style>.'.$this->element['name'].'+.optional{display:none;}</style><div class="'.$this->element['name'].'"></div>';
	}*/

	/**
	 * Method to get the field title.
	 *
	 * @return  string  The field title.
	 *
	 * @since   11.1
	 */
	/*protected function getTitle()
	{
		return ' ';
	}*/
}
