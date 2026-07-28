<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

require_once(JPATH_COMPONENT.'/helpers/trigger_com_config.php');

require_once(JPATH_COMPONENT.'/defines.php');

ini_set('display_errors', false);
error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

$version=new JVersion();
if($version->RELEASE=='3.0'){
	JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/j30/html');
	JFormHelper::addRulePath(JPATH_COMPONENT.'/helpers/j30/rule');
	define('JSNPREFIX','j30');
}
else define('JSNPREFIX','');

$input = JFactory::getApplication()->input;

if (!JFactory::getUser()->authorise('core.manage', 'com_jsn') && $input->get('view','fields')!='opField' && $input->get('view','fields')!='users')
{
	return JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'));
}
if (!JFactory::getUser()->authorise('core.manage', 'com_users') && $input->get('view','fields')=='users')
{
	return JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'));
}

$lang = JFactory::getLanguage();
$lang->load('com_users');
$lang->load('com_jsn',JPATH_SITE);
JLoader::register('UsersHelper', __DIR__ . '/../com_users/helpers/users.php');

$task = $input->get('task');

$controller	= JControllerLegacy::getInstance('Jsn');
$controller->execute($input->get('task'));
$controller->redirect();
