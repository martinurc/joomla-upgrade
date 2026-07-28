<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnUsernameFieldHelper
{
	public static function create($alias)
	{
		
	}
	
	public static function delete($alias)
	{
		
	}
	
	public static function getXml($item)
	{
		$xml='';
		if(JFactory::getApplication()->isSite())
		{
			require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
			$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
			if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
			$placeholder=($item->params->get('username_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('username_placeholder','')).'"' : '');
			
			if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
			elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
			else $readonly='';
			if($item->params->get('username_regex','')!='custom') $regex=($item->params->get('username_regex','')!='' ? 'class="validate-pattern '.$item->params->get('field_cssclass','').'" pattern="'.$item->params->get('username_regex','').'"' : 'class="validate-username '.$item->params->get('field_cssclass','').'"');
			else $regex='class="validate-pattern '.$item->params->get('field_cssclass','').'" pattern="'.$item->params->get('username_customregex','').'"';

			$xml='
				<field name="username" type="textfull"
					'.$regex.'
					description="COM_USERS_DESIRED_USERNAME"
					filter="username"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					message="COM_USERS_PROFILE_USERNAME_MESSAGE"
					required="true"
					'.$placeholder.'
					'.$readonly.'
					size="30"
					validate="username"
					message-regex="'.JsnHelper::xmlentities($item->params->get('username_messageregex','')).'"
				/>
			';
		}
		else{
			$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
			$placeholder=($item->params->get('username_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('username_placeholder','')).'"' : '');
			$xml='
				<field name="username" type="text"
					description="COM_USERS_USER_FIELD_USERNAME_DESC"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					required="true"
					size="30"
					'.$placeholder.'
					class="'.$item->params->get('field_cssclass','').'"
				/>
			';
		}
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		
	}
	
	public static function getSearchInput($field)
	{
		$return='<input id="jform_'.str_replace('-','_',$field->alias).'" type="text" placeholder="'.JText::_('COM_JSN_SEARCHFOR').' '.JText::_($field->title).'..." name="'.$field->alias.'" value="'.JFactory::getApplication()->input->get($field->alias,'','raw').'"/>';
		return $return;
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$db=JFactory::getDbo();
		if($field->params->get('username_searchmode','like')=='like') $query->where('a.'.$db->quoteName('username').' LIKE '.$db->quote('%'.JFactory::getApplication()->input->get('username',null,'raw').'%'));
		else $query->where('LOWER(a.'.$db->quoteName('username').') = LOWER('.$db->quote(JFactory::getApplication()->input->get('username',null,'raw')).')');
	}

}
