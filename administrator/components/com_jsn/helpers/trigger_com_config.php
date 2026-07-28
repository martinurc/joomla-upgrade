<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

function onConfigurationBeforeSave(){
	
		$db = JFactory::getDbo();
		$config = JComponentHelper::getParams('com_jsn');
		
		switch($config->get('namestyle', 'FIRSTNAME_LASTNAME')){
			case 'FIRSTNAME_LASTNAME':
				$unpublish=array($db->quote('secondname'));
				$publish=array($db->quote('firstname'),$db->quote('lastname'));
			break;
			case 'FIRSTNAME_SECONDNAME_LASTNAME':
				$unpublish=array();
				$publish=array($db->quote('firstname'),$db->quote('lastname'),$db->quote('secondname'));
			break;
			case 'FIRSTNAME':
				$unpublish=array($db->quote('secondname'),$db->quote('lastname'));
				$publish=array($db->quote('firstname'));
			break;
		}
		if(count($unpublish)>0)
		{
			$query = $db->getQuery(true);
			$query->update("#__jsn_fields");
			$query->set($db->quoteName('published').' = 0');
			$query->where('alias IN ('. implode(', ',$unpublish) .')');
			$db->setQuery($query);
			$db->execute();
		}
		if(count($publish)>0)
		{
			$query = $db->getQuery(true);
			$query->update("#__jsn_fields");
			$query->set($db->quoteName('published').' = 1');
			$query->where('alias IN ('. implode(', ',$publish) .')');
			$db->setQuery($query);
			$db->execute();
		}
	
	
		$query = $db->getQuery(true);
		$query->update("#__jsn_fields");
		$query->set($db->quoteName('published').' = '. ($config->get('avatar', 1) ? 1 : 0) );
		$query->where('alias = '.$db->quote('avatar'));
		$db->setQuery($query);
		$db->execute();
		
	
}
onConfigurationBeforeSave();
?>