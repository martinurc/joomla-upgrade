<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


function JsnBuildRoute(&$query)
{
	$segments = array();

	// get a menu item based on Itemid or currently active
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$params = JComponentHelper::getParams('com_jsn');
	$advanced = $params->get('sef_advanced_link', 0);
	$sef_suffix=JFactory::getConfig()->get('sef_suffix',0);

	if(isset($query['view']))
		switch($query['view'])
		{
			case '':
			case 'profile':
				if(isset($query['Itemid']) && is_int($query['Itemid'])) {
					if($menu->getItem($query['Itemid'])->link!='index.php?option=com_jsn&view=profile') unset($query['Itemid']);
				}
				if(!isset($query['Itemid'])) {
					$active=$menu->getActive();
					if(isset($active->link) && $active->link=='index.php?option=com_jsn&view=profile')
					{
						$query['Itemid']=$active->id;
					}
					else
					{
						$profileMenu=$menu->getItems('link','index.php?option=com_jsn&view=profile',true);
						if(isset($profileMenu->id))
						{
							$query['Itemid']=$profileMenu->id;
						}
						else{
							unset($query['Itemid']);
						}
					}
				}
				unset($query['view']);
				
				if(isset($query['id']))
				{
					static $username_cache;
					if(!isset($username_cache[$query['id']]))
					{
						if($params->get('sef_with','username') == 'id') {
							$segments[]=$query['id'];
							$username_cache[$query['id']]=$query['id'];
						}
						else {
							$db = JFactory::getDbo();
							$dbquery = $db->getQuery(true);
							$dbquery->select('a.username')->from('#__users AS a')->where('a.id = '. (int) $query['id']);
							$db->setQuery( $dbquery );
							if($username=$db->loadResult())
							{
								if(!$sef_suffix && strpos(' '.$username,'.')>0) $username.='.html';
								$segments[]=urlencode($username);
								$username_cache[$query['id']]=$username;
							}
						}
					}
					else
					{
						$segments[]=urlencode($username_cache[$query['id']]);
					}
					
					unset($query['id']);
				}
			break;
			case 'list':
			case 'search':
				unset($query['view']);
			break;
			case 'social':
				$active=$menu->getActive();
				if(isset($active->link) && $active->link=='index.php?option=com_jsn&view=social')
				{
					$query['Itemid']=$active->id;
				}
				else
				{
					$profileMenu=$menu->getItems('link','index.php?option=com_jsn&view=social',true);
					if(isset($profileMenu->id))
					{
						$query['Itemid']=$profileMenu->id;
					}
					else{
						$query['Itemid']=$menu->getDefault()->id;
					}
				}
				unset($query['view']);
				if(isset($query['route']))
				{
					$segments[]=$query['route'];
					unset($query['route']);
				}
			break;
			default:
			break;
		}
	
	return $segments;
}

function JsnParseRoute($segments)
{
	$vars = array();

	//Get the active menu item.
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$item = $menu->getActive();
	$params = JComponentHelper::getParams('com_jsn');
	$advanced = $params->get('sef_advanced_link', 0);

	// Count route segments
	$count = count($segments);

	if (!isset($item) || ($segments[0]=='profile' && isset($item->query['view']) && $item->query['view']!='social'))
	{
		$vars['view'] = 'profile';
	}
	else
	{
		$vars['view'] = $item->query['view'];
	}
	// Standard routing for newsfeeds.
	switch($vars['view'])
	{
		case 'profile' :
			static $id_cache=null;
			if($id_cache==null)
			{
				if($params->get('sef_with','username') == 'id') {
					$vars['id'] = $segments[$count-1];
					$id_cache=$segments[$count-1];
				}
				else {
					$db = JFactory::getDbo();
					$dbquery = $db->getQuery(true);
					$dbquery->select('a.id')->from('#__users AS a')->where('a.username = '. $db->quote(str_replace('.html','',$segments[$count-1])).' OR a.username = '. $db->quote(str_replace(':','-',str_replace('.html','',$segments[$count-1]))  )   );
					$db->setQuery( $dbquery );
					if($id=$db->loadResult())
					{
						$vars['id'] = $id;
						$id_cache=$id;
					}
				}
			}
			else
			{
				$vars['id']=$id_cache;
			}
		break;
		case 'social' :
			$vars['route'] = '/'.implode('/',$segments);
		break;
	}
	return $vars;
}
