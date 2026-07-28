<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


JFormHelper::loadFieldClass('hidden');

/**
 * Form Field class for the Joomla Platform.
 * Provides spacer markup to be used in form layouts.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldJsnprivacy extends JFormFieldHidden
{

	public $type = 'Jsnprivacy';
	
	protected function getInput()
	{
		$doc = JFactory::getDocument();
		$dir = $doc->direction;

		$config = JComponentHelper::getParams('com_jsn');
		$html=array();
		$jsnsocial_enabled = JPluginHelper::isEnabled('jsn', 'socialnetwork');
		if($jsnsocial_enabled)
		{
			$html[]='
			  <a id="btn_'.$this->id.'" class="privacy_btn" data-toggle="dropdown" href="#">
			    <i></i>
			    <span class="caret"></span>
			  </a>
			  <ul class="dropdown-menu pull-'.($dir=='rtl' ? 'left' : 'right').'" id="opt_'.$this->id.'">
				<li><a href="#" rel="0"><i class="jsn-icon jsn-icon-users green"></i> '.JText::_('COM_JSN_PUBLIC').'</a></li>
				<li><a href="#" rel="1"><i class="jsn-icon jsn-icon-user orange"></i> '.JText::_('COM_JSN_FRIENDSONLY').'</a></li>
				<li><a href="#" rel="99"><i class="jsn-icon jsn-icon-user-secret red"></i> '.JText::_('COM_JSN_PRIVATE').'</a></li>
			  </ul>
			';
		}
		else
		{
			$html[]='
			  <a id="btn_'.$this->id.'" class="privacy_btn" data-toggle="dropdown" href="#">
			    <i></i>
			    <span class="caret"></span>
			  </a>
			  <ul class="dropdown-menu pull-'.($dir=='rtl' ? 'left' : 'right').'" id="opt_'.$this->id.'">
				'.($config->get('profileACL',2)==2 ? '<li><a href="#" rel="0"><i class="jsn-icon jsn-icon-users green"></i> '.JText::_('COM_JSN_PUBLIC').'</a></li>': '').'
				<li><a href="#" rel="1"><i class="jsn-icon jsn-icon-user orange"></i> '.JText::_('COM_JSN_REGISTERED').'</a></li>
				<li><a href="#" rel="99"><i class="jsn-icon jsn-icon-user-secret red"></i> '.JText::_('COM_JSN_PRIVATE').'</a></li>
			  </ul>
			';
		}
		//'<a href="#'.$this->id.'" class="privacy_btn btn"><i class="icon-locked"></i></a>';
		$html[]=parent::getInput();
		return implode('', $html);
	}

}
