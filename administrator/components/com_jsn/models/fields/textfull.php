<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;



JFormHelper::loadFieldClass('text');

/**
 * Form Field class for the Joomla Platform.
 * Provides spacer markup to be used in form layouts.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldTextfull extends JFormFieldText
{

	public $type = 'Text';

	protected function getInput()
	{
		$html = parent::getInput();
		if(!empty($this->element['pattern'])) {
			$html = preg_replace('/pattern=/', 'jsn-pattern=', $html, 1);
			if(isset($this->element['message-regex'])) $html.= '<div class="message-regex">'.$this->element['message-regex'].'</div>';
		}
		return $html;
	}



}
