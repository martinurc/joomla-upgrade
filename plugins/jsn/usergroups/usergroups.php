<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

class PlgJsnUsergroups extends JPlugin
{
	public function triggerProfileUpdate($user,&$data,$changed,$isNew)
	{
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if (JSN_TYPE == 'free') return;
		 
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');			
		static $fields=null;
		if(!$fields)
		{
			$db=JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.published = 1')->order($db->escape('a.lft') . ' ASC');
			$db->setQuery( $query );
			$fields = $db->loadObjectList('alias');
		}
		$userData= (object) $data;
		foreach($fields as $field)
		{
			if(!isset($data[$field->alias])) continue;
			
			// Load Conditions
			if(empty($field->conditions)) $field->conditions = array();
			elseif(is_string($field->conditions)) $field->conditions=json_decode($field->conditions);
			
			foreach($field->conditions as $condition)
			{
				if( !in_array($condition->action,array('usergroups_add','usergroups_remove') ) || empty($condition->usergroups_target) ) continue;
				$twoWays = $condition->two_ways;
				$usergroups = $condition->usergroups_target;
				
				if($condition->to) $value=$condition->custom_value;
				else 
				{
					$alias=$condition->to;
					if(isset($userData->$alias)) $value=$userData->$alias;
					else $value='';
					if(is_array($value)) $value=implode(',',$value);
				}
				$alias=$field->alias;
				if(!isset($userData->$alias)) $userValue='';
				else $userValue=$userData->$alias;
				
				if(is_array($userValue)) $userValue=implode(',',$userValue);
				
				switch($condition->operator)
				{
					case 1:
						if($userValue==$value)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::addUserToGroup($user,$usergroup);
								else JsnHelper::removeUserFromGroup($user,$usergroup);
							}
						}
						elseif($twoWays)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::removeUserFromGroup($user,$usergroup);
								else JsnHelper::addUserToGroup($user,$usergroup);
							}
						}
					break;
					case 2:
						if($userValue>$value)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::addUserToGroup($user,$usergroup);
								else JsnHelper::removeUserFromGroup($user,$usergroup);
							}
						}
						elseif($twoWays)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::removeUserFromGroup($user,$usergroup);
								else JsnHelper::addUserToGroup($user,$usergroup);
							}
						}
					break;
					case 3:
						if($userValue<$value)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::addUserToGroup($user,$usergroup);
								else JsnHelper::removeUserFromGroup($user,$usergroup);
							}
						}
						elseif($twoWays)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::removeUserFromGroup($user,$usergroup);
								else JsnHelper::addUserToGroup($user,$usergroup);
							}
						}
					break;
					case 4:
						if(strpos(' '.$userValue,$value)>0)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::addUserToGroup($user,$usergroup);
								else JsnHelper::removeUserFromGroup($user,$usergroup);
							}
						}
						elseif($twoWays)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::removeUserFromGroup($user,$usergroup);
								else JsnHelper::addUserToGroup($user,$usergroup);
							}
						}
					break;
					case 5:
						if($userValue!=$value)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::addUserToGroup($user,$usergroup);
								else JsnHelper::removeUserFromGroup($user,$usergroup);
							}
						}
						elseif($twoWays)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::removeUserFromGroup($user,$usergroup);
								else JsnHelper::addUserToGroup($user,$usergroup);
							}
						}
					break;
					case 6:
						if(!(strpos(' '.$userValue,$value)>0))
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::addUserToGroup($user,$usergroup);
								else JsnHelper::removeUserFromGroup($user,$usergroup);
							}
						}
						elseif($twoWays)
						{
							foreach($usergroups as $usergroup)
							{
								if($condition->action=='usergroups_add') JsnHelper::removeUserFromGroup($user,$usergroup);
								else JsnHelper::addUserToGroup($user,$usergroup);
							}
						}
					break;
				}
			}
		}
	}
}

?>