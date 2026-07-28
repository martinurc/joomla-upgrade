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

abstract class ModJsnUsersHelper
{
	public static function getList(&$params)
	{
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
		$appParams->set('where',$params->get('where',''));
		
		$items = $model->getItems();
		
		return $items;
	}
}
