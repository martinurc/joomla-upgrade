<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;



JFormHelper::loadFieldClass('password');

/**
 * Form Field class for the Joomla Platform.
 * Provides spacer markup to be used in form layouts.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldPasswordfull extends JFormFieldPassword
{

	public $type = 'Passwordfull';

	protected function getInput()
	{
		$version = new JVersion();
		if($version->getShortVersion() < '3.8')
		{
			$configJsn=JComponentHelper::getParams('com_jsn');
			if($configJsn->get('passwordstrengthmeter',0)){
				JHtml::_('behavior.framework');
				$doc = JFactory::getDocument();
				$doc->addScript(JURI::root().'media/system/js/passwordstrength.js');
			}
		}
		$html = parent::getInput();
		if(strpos($html, 'autocomplete') === false) $html = str_replace('type="password"','autocomplete="new-password" type="password"',$html);
		$html.= '<div class="message-regex">'.JText::_('COM_JSN_NOT_VALID_PASSWORD').'</div>';
		return $html;
	}



}
