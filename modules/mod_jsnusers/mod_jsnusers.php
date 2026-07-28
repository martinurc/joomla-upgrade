<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/


defined('_JEXEC') or die;

require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
if (JSN_TYPE == 'free') return;

require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$lang = JFactory::getLanguage();
$lang->load('com_jsn');

$list = ModJsnUsersHelper::getList($params);

if(!is_array($list) || empty($list)) return true;

$db=JFactory::getDbo();
$query=$db->getQuery(true);
$query->select('alias,title')->from('#__jsn_fields');
$db->setQuery($query);
$fields_title=$db->loadAssocList('alias');
$fields_title['formatname']=array('alias'=>'formatname','title'=>'NAME');

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

require JModuleHelper::getLayoutPath('mod_jsnusers', $params->get('layout', 'default'));
