<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/


defined('_JEXEC') or die;

JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_jsn/models', 'JsnModel');

abstract class ModJsnMapHelper
{
	public static function getList(&$params)
	{
		global $JSNSOCIAL;
		$model = JModelLegacy::getInstance('List', 'JsnModel', array('ignore_request' => true));
		$model->filter=false;
		
		// Set application parameters in model
		$app = JFactory::getApplication();
		$appParams = $app->getParams();
		$appParams->set('orderDir','ASC');
		$model->setState('params', $appParams);
		
		// Set the filters based on the module params
		$model->setState('list.start', 0);
		$model->setState('list.limit', (int) $params->get('display_num', 10));
		
		$appParams->set('orderDir',$params->get('orderDir','ASC'));
		$appParams->set('orderCol',$params->get('orderCol','name'));
		$appParams->set('orderDir1',$params->get('orderDir1','ASC'));
		$appParams->set('orderCol1',$params->get('orderCol1',''));
		$appParams->set('orderDir2',$params->get('orderDir2','ASC'));
		$appParams->set('orderCol2',$params->get('orderCol2',''));

		$main_where=array();
		
		$db=JFactory::getDbo();
		$map_aliases=$params->def('mapfield', false);
		if(is_string($map_aliases)) $map_aliases=array($map_aliases); // Compatibility with old gmap module

		foreach($map_aliases as $map_alias){
			
			$map_lat=$db->quoteName($map_alias.'_lat');
			
			$where = "$map_lat <> ''";
			
			$user=JFactory::getUser();
			if($JSNSOCIAL && !$user->guest)
			{
				$db->setQuery("SELECT friend_id FROM #__jsnsocial_friends WHERE user_id = ".$user->id);
				$friends = $db->loadColumn();
				if(empty($friends)) $friends=array(0);
			}
			
			// Load Map Field
			$queryField = $db->getQuery(true);
			$queryField->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.alias = '.$db->quote($map_alias))->where('a.published = 1');
			$db->setQuery( $queryField );
			$field = $db->loadObject();
			if($field){
				// Load Field Registry
				$registry = new JRegistry;
				$registry->loadString($field->params);
				$field->params = $registry;
				
				$skipPrivacy=$user->authorise('core.edit', 'com_users');
				
				// Field Privacy
				if(!$skipPrivacy && $field->params->get('privacy',0)){
					if($user->guest)
					{
						if($field->params->get('privacy_default',0)==0) $where.=' AND (b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').' OR b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').')';
						else $where.=' AND (b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').')';
					} 
					else
					{
						if($JSNSOCIAL)
						{
							if($field->params->get('privacy_default',0)==0) 
								$where.=' AND (((b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').' OR b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').') || (b.id IN ('.implode(',',$friends).') AND b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"1"%').')) OR b.id='.$user->id.')';
							if($field->params->get('privacy_default',0)==1) 
								$where.=' AND (((b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').') || (b.id IN ('.implode(',',$friends).') AND (b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"1"%').' || b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').'))) OR b.id='.$user->id.')';
							if($field->params->get('privacy_default',0)==99) 
								$where.=' AND (((b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').') || (b.id IN ('.implode(',',$friends).') AND b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"1"%').')) OR b.id='.$user->id.')';
						}
						else
						{
							if($field->params->get('privacy_default',0)==99) $where.=' AND ((b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'":"99"%').' AND b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').') OR b.id='.$user->id.')';
							else $where.=' AND (b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'":"99"%').' OR b.id='.$user->id.')';
						}
					}
				}

				
			}
			
			if($params->def('mapenableradius', false) && $params->def('mapradiusrange', false))
			{
				$radius=$params->def('mapradiusrange', false);
				if($params->def('mapradiusunit','Km')=='Km') $const=6371; else $const=3959;
				$where.=' AND (( '.$const.' * acos( cos( radians('.$params->def('mapradiuslat',0).') ) * cos( radians( b.'.$db->quoteName($map_alias.'_lat').' ) ) * cos( radians( b.'.$db->quoteName($map_alias.'_lng').' ) - radians('.$params->def('mapradiuslng',0).') ) + sin( radians('.$params->def('mapradiuslat',0).') ) * sin( radians( b.'.$db->quoteName($map_alias.'_lat').' ) ) ) ) < '.$radius.')';
			}

			$main_where[] = $where;

		}

		$where = '(('.implode(') OR (', $main_where).'))';


		if(!empty($params->get('where',''))) $where = '('.$where.') AND '. $params->get('where','');
		
		if($params->get('syncUserlist',0))
		{
			global $LISTIDS;
			if(is_array($LISTIDS) && !count($LISTIDS)) return array();
			if(is_array($LISTIDS))
			{
				//$params->set('mapcluster', false);
				$where.=' AND a.id IN ('.implode(',',$LISTIDS).')';
			}
		}
		
		$appParams->set('where',$where);
		
		$items = $model->getItems();
		
		return $items;
	}
}
