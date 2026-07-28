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

$list = ModJsnMapHelper::getList($params);

// Load JS
$jsnConfig=JComponentHelper::getParams('com_jsn');
$cluster = $params->def('mapcluster', 0);
$xml = simplexml_load_file(JPATH_ADMINISTRATOR .'/components/com_jsn/jsn.xml');
$version = (string)$xml->version;
$doc = JFactory::getDocument();
$doc->addScript('https://maps.googleapis.com/maps/api/js?libraries=places&key='.$jsnConfig->get('googlemaps_apikey',''));
if($cluster)
  $doc->addScript(JURI::root().'components/com_jsn/assets/js/markerclusterer.min.js?v='.$version);

// Check if there is some user
if(!is_array($list) || empty($list)) {echo '<div id="jsnmap'.$module->id.'-canvas" class="jsn-map empty" style="height:'.$params->def('mapheight', 400).'px;"></div>';return true;}

// Load Fields Titles
$db=JFactory::getDbo();
$query=$db->getQuery(true);
$query->select('alias,title')->from('#__jsn_fields');
$db->setQuery($query);
$fields_title=$db->loadAssocList('alias');
$fields_title['formatname']=array('alias'=>'formatname','title'=>'NAME');

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

require JModuleHelper::getLayoutPath('mod_jsnmap', $params->get('layout', 'default'));
