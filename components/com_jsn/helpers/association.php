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
 * Content Component Association Helper
 *
 * @package     Joomla.Site
 * @subpackage  com_content
 * @since       3.0
 */
abstract class JsnHelperAssociation
{
	public static function getAssociations($id = 0, $view = null)
	{
		$app=JFactory::getApplication();
		$jinput = JFactory::getApplication()->input;
		$langs = JLanguageHelper::getLanguages();
		
		$menu 	= $app->getMenu();
		
		$active = $menu->getActive();

		if ($active && $jinput->get('view') == 'profile')
		{
			$associations = MenusHelper::getAssociations($active->id);
		}
		else return array();
		
		$return=array();
		foreach($langs as $lang) {
			if(isset($associations[$lang->lang_code]))
				$return[$lang->lang_code]='index.php?option=com_jsn&view=profile&Itemid='.$associations[$lang->lang_code].'&id='.$jinput->getInt('id','');
			else
				$return[$lang->lang_code]='index.php?option=com_jsn&view=profile&Itemid='.$active->id.'&id='.$jinput->getInt('id','');
		}

		return $return;
	}
}
