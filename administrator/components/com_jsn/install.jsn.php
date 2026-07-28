<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.12001.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class com_JsnInstallerScript {
	
	

	/**
	* Method to install the extension
	* $parent is the class calling this method
	*
	* @return void
	*/
	function install($parent) 
	{
		
	}

	/**
	* Method to uninstall the extension
	* $parent is the class calling this method
	*
	* @return void
	*/
	function uninstall($parent) 
	{

	}

	/**
	* Method to update the extension
	* $parent is the class calling this method
	*
	* @return void
	*/
	function update($parent) 
	{
		
	}

	/**
	* Method to run before an install/update/uninstall method
	* $parent is the class calling this method
	* $type is the type of change (install, update or discover_install)
	*
	* @return void
	*/
	function preflight($type, $parent) 
	{
		$files = JPATH_SITE.'/administrator/components/com_jsn/helpers/fields/*';
		foreach (glob($files) as $deletefile)
		{
			unlink($deletefile);
		}
		$files = JPATH_SITE.'/administrator/components/com_jsn/models/fields/*';
		foreach (glob($files) as $deletefile)
		{
			unlink($deletefile);
		}
	}

	/**
	* Method to run after an install/update/uninstall method
	* $parent is the class calling this method
	* $type is the type of change (install, update or discover_install)
	*
	* @return void
	*/
	function postflight($type, $parent) 
	{
		//$app = JFactory::getApplication();
		$db=JFactory::getDbo();

		// Add Column for versions 1.1.x to 1.2.x
		try {
			$query ="ALTER TABLE `#__jsn_fields` ADD `edit` tinyint(1) NOT NULL DEFAULT '0'";
			$db->setQuery($query);
			$db->execute();
			$query ="UPDATE `#__jsn_fields` SET `edit`=1 WHERE `level`=2 AND `params` NOT LIKE '%\"hideonedit\":\"1\"%'";
			$db->setQuery($query);
			$db->execute();
			$query ="UPDATE `#__jsn_fields` SET `alias`='registerdate',`path`='default/registerdate',`edit`='0' WHERE `id`=10";
			$db->setQuery($query);
			$db->execute();
			$query ="UPDATE `#__jsn_fields` SET `edit`='0' WHERE `id`=11";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Add Column for versions 1.2.x to 1.3.x
		try {
			$query ="ALTER TABLE `#__jsn_fields` ADD `accessview` int(10) unsigned NOT NULL DEFAULT '0'";
			$db->setQuery($query);
			$db->execute();
			$query ="UPDATE `#__jsn_fields` SET `accessview`=1;";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Remove Old Broken Package reference
		try {
			$query ="DELETE FROM `#__extensions` WHERE `element` = 'pkg_pkg_jsn'";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Add Column Instagram_id for versions 2.0.x to 2.1.x
		try {
			$query ="ALTER TABLE `#__jsn_users` MODIFY COLUMN `facebook_id` varchar(200) NOT NULL";
			$db->setQuery($query);
			$db->execute();
			$query ="ALTER TABLE `#__jsn_users` ADD `instagram_id` varchar(50) NOT NULL";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Add Column EditBackend for versions 2.0.x to 2.6.0
		try {
			$query ="ALTER TABLE `#__jsn_fields` ADD `editbackend` tinyint(1) NOT NULL DEFAULT '1'";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Fix root record in #__jsn_fields table
		try {
			$query ="UPDATE `#__jsn_fields` SET `published` = 1 WHERE `id` = 1";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Firstname and Lastname need to be required also in backend
		try {
			$query ="UPDATE `#__jsn_fields` SET `required` = 2 WHERE `id` IN (4,6)";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Add Column Conditions for versions 2.3+
		try {

			$query ="ALTER TABLE `#__jsn_fields` ADD `conditions` text NOT NULL";
			$db->setQuery($query);
			$db->execute();

			// Convert old conditions to new system
			$query = 'SELECT * FROM #__jsn_fields';
			$db->setQuery($query);
			$fields = $db->loadObjectList('alias');
			foreach($fields as $field)
			{
				if(empty($field->params)) continue;
				
				$params=json_decode($field->params);
				if(!isset($params->condition_operator)) continue;

				$conditions=array();
				// Condition 0
				if($params->condition_operator > 0){
					if(!empty($params->condition_hide)){
						$c = new stdClass();
						$c->operator = $params->condition_operator;
						$c->to = $params->condition_field;
						$c->custom_value = $params->condition_custom;
						$c->action = ($params->condition_action=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide;
						$c->two_ways = $params->condition_twoways;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups)){
						$c = new stdClass();
						$c->operator = $params->condition_operator;
						$c->to = $params->condition_field;
						$c->custom_value = $params->condition_custom;
						$c->action = ($params->condition_ugaction=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups;
						$c->two_ways = $params->condition_twoways;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 1
				if($params->condition_operator1 > 0){
					if(!empty($params->condition_hide1)){
						$c = new stdClass();
						$c->operator = $params->condition_operator1;
						$c->to = $params->condition_field1;
						$c->custom_value = $params->condition_custom1;
						$c->action = ($params->condition_action1=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide1;
						$c->two_ways = $params->condition_twoways1;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups1)){
						$c = new stdClass();
						$c->operator = $params->condition_operator1;
						$c->to = $params->condition_field1;
						$c->custom_value = $params->condition_custom1;
						$c->action = ($params->condition_ugaction1=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups1;
						$c->two_ways = $params->condition_twoways1;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 2
				if($params->condition_operator2 > 0){
					if(!empty($params->condition_hide2)){
						$c = new stdClass();
						$c->operator = $params->condition_operator2;
						$c->to = $params->condition_field2;
						$c->custom_value = $params->condition_custom2;
						$c->action = ($params->condition_action2=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide2;
						$c->two_ways = $params->condition_twoways2;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups2)){
						$c = new stdClass();
						$c->operator = $params->condition_operator2;
						$c->to = $params->condition_field2;
						$c->custom_value = $params->condition_custom2;
						$c->action = ($params->condition_ugaction2=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups2;
						$c->two_ways = $params->condition_twoways2;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 3
				if($params->condition_operator3 > 0){
					if(!empty($params->condition_hide3)){
						$c = new stdClass();
						$c->operator = $params->condition_operator3;
						$c->to = $params->condition_field3;
						$c->custom_value = $params->condition_custom3;
						$c->action = ($params->condition_action3=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide3;
						$c->two_ways = $params->condition_twoways3;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups3)){
						$c = new stdClass();
						$c->operator = $params->condition_operator3;
						$c->to = $params->condition_field3;
						$c->custom_value = $params->condition_custom3;
						$c->action = ($params->condition_ugaction3=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups3;
						$c->two_ways = $params->condition_twoways3;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 4
				if($params->condition_operator4 > 0){
					if(!empty($params->condition_hide4)){
						$c = new stdClass();
						$c->operator = $params->condition_operator4;
						$c->to = $params->condition_field4;
						$c->custom_value = $params->condition_custom4;
						$c->action = ($params->condition_action4=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide4;
						$c->two_ways = $params->condition_twoways4;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups4)){
						$c = new stdClass();
						$c->operator = $params->condition_operator4;
						$c->to = $params->condition_field4;
						$c->custom_value = $params->condition_custom4;
						$c->action = ($params->condition_ugaction4=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups4;
						$c->two_ways = $params->condition_twoways4;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 5
				if($params->condition_operator5 > 0){
					if(!empty($params->condition_hide5)){
						$c = new stdClass();
						$c->operator = $params->condition_operator5;
						$c->to = $params->condition_field5;
						$c->custom_value = $params->condition_custom5;
						$c->action = ($params->condition_action5=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide5;
						$c->two_ways = $params->condition_twoways5;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups5)){
						$c = new stdClass();
						$c->operator = $params->condition_operator5;
						$c->to = $params->condition_field5;
						$c->custom_value = $params->condition_custom5;
						$c->action = ($params->condition_ugaction5=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups5;
						$c->two_ways = $params->condition_twoways5;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 6
				if($params->condition_operator6 > 0){
					if(!empty($params->condition_hide6)){
						$c = new stdClass();
						$c->operator = $params->condition_operator6;
						$c->to = $params->condition_field6;
						$c->custom_value = $params->condition_custom6;
						$c->action = ($params->condition_action6=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide6;
						$c->two_ways = $params->condition_twoways6;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups6)){
						$c = new stdClass();
						$c->operator = $params->condition_operator6;
						$c->to = $params->condition_field6;
						$c->custom_value = $params->condition_custom6;
						$c->action = ($params->condition_ugaction6=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups6;
						$c->two_ways = $params->condition_twoways6;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 7
				if($params->condition_operator7 > 0){
					if(!empty($params->condition_hide7)){
						$c = new stdClass();
						$c->operator = $params->condition_operator7;
						$c->to = $params->condition_field7;
						$c->custom_value = $params->condition_custom7;
						$c->action = ($params->condition_action7=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide7;
						$c->two_ways = $params->condition_twoways7;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups7)){
						$c = new stdClass();
						$c->operator = $params->condition_operator7;
						$c->to = $params->condition_field7;
						$c->custom_value = $params->condition_custom7;
						$c->action = ($params->condition_ugaction7=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups7;
						$c->two_ways = $params->condition_twoways7;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 8
				if($params->condition_operator8 > 0){
					if(!empty($params->condition_hide8)){
						$c = new stdClass();
						$c->operator = $params->condition_operator8;
						$c->to = $params->condition_field8;
						$c->custom_value = $params->condition_custom8;
						$c->action = ($params->condition_action8=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide8;
						$c->two_ways = $params->condition_twoways8;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups8)){
						$c = new stdClass();
						$c->operator = $params->condition_operator8;
						$c->to = $params->condition_field8;
						$c->custom_value = $params->condition_custom8;
						$c->action = ($params->condition_ugaction8=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups8;
						$c->two_ways = $params->condition_twoways8;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 9
				if($params->condition_operator9 > 0){
					if(!empty($params->condition_hide9)){
						$c = new stdClass();
						$c->operator = $params->condition_operator9;
						$c->to = $params->condition_field9;
						$c->custom_value = $params->condition_custom9;
						$c->action = ($params->condition_action9=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide9;
						$c->two_ways = $params->condition_twoways9;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups9)){
						$c = new stdClass();
						$c->operator = $params->condition_operator9;
						$c->to = $params->condition_field9;
						$c->custom_value = $params->condition_custom9;
						$c->action = ($params->condition_ugaction9=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups9;
						$c->two_ways = $params->condition_twoways9;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 10
				if($params->condition_operator10 > 0){
					if(!empty($params->condition_hide10)){
						$c = new stdClass();
						$c->operator = $params->condition_operator10;
						$c->to = $params->condition_field10;
						$c->custom_value = $params->condition_custom10;
						$c->action = ($params->condition_action10=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide10;
						$c->two_ways = $params->condition_twoways10;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups10)){
						$c = new stdClass();
						$c->operator = $params->condition_operator10;
						$c->to = $params->condition_field10;
						$c->custom_value = $params->condition_custom10;
						$c->action = ($params->condition_ugaction10=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups10;
						$c->two_ways = $params->condition_twoways10;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 11
				if($params->condition_operator11 > 0){
					if(!empty($params->condition_hide11)){
						$c = new stdClass();
						$c->operator = $params->condition_operator11;
						$c->to = $params->condition_field11;
						$c->custom_value = $params->condition_custom11;
						$c->action = ($params->condition_action11=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide11;
						$c->two_ways = $params->condition_twoways11;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups11)){
						$c = new stdClass();
						$c->operator = $params->condition_operator11;
						$c->to = $params->condition_field11;
						$c->custom_value = $params->condition_custom11;
						$c->action = ($params->condition_ugaction11=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups11;
						$c->two_ways = $params->condition_twoways11;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 12
				if($params->condition_operator12 > 0){
					if(!empty($params->condition_hide12)){
						$c = new stdClass();
						$c->operator = $params->condition_operator12;
						$c->to = $params->condition_field12;
						$c->custom_value = $params->condition_custom12;
						$c->action = ($params->condition_action12=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide12;
						$c->two_ways = $params->condition_twoways12;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups12)){
						$c = new stdClass();
						$c->operator = $params->condition_operator12;
						$c->to = $params->condition_field12;
						$c->custom_value = $params->condition_custom12;
						$c->action = ($params->condition_ugaction12=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups12;
						$c->two_ways = $params->condition_twoways12;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 13
				if($params->condition_operator13 > 0){
					if(!empty($params->condition_hide13)){
						$c = new stdClass();
						$c->operator = $params->condition_operator13;
						$c->to = $params->condition_field13;
						$c->custom_value = $params->condition_custom13;
						$c->action = ($params->condition_action13=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide13;
						$c->two_ways = $params->condition_twoways13;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups13)){
						$c = new stdClass();
						$c->operator = $params->condition_operator13;
						$c->to = $params->condition_field13;
						$c->custom_value = $params->condition_custom13;
						$c->action = ($params->condition_ugaction13=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups13;
						$c->two_ways = $params->condition_twoways13;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 14
				if($params->condition_operator14 > 0){
					if(!empty($params->condition_hide14)){
						$c = new stdClass();
						$c->operator = $params->condition_operator14;
						$c->to = $params->condition_field14;
						$c->custom_value = $params->condition_custom14;
						$c->action = ($params->condition_action14=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide14;
						$c->two_ways = $params->condition_twoways14;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups14)){
						$c = new stdClass();
						$c->operator = $params->condition_operator14;
						$c->to = $params->condition_field14;
						$c->custom_value = $params->condition_custom14;
						$c->action = ($params->condition_ugaction14=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups14;
						$c->two_ways = $params->condition_twoways14;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 15
				if($params->condition_operator15 > 0){
					if(!empty($params->condition_hide15)){
						$c = new stdClass();
						$c->operator = $params->condition_operator15;
						$c->to = $params->condition_field15;
						$c->custom_value = $params->condition_custom15;
						$c->action = ($params->condition_action15=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide15;
						$c->two_ways = $params->condition_twoways15;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups15)){
						$c = new stdClass();
						$c->operator = $params->condition_operator15;
						$c->to = $params->condition_field15;
						$c->custom_value = $params->condition_custom15;
						$c->action = ($params->condition_ugaction15=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups15;
						$c->two_ways = $params->condition_twoways15;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 16
				if($params->condition_operator16 > 0){
					if(!empty($params->condition_hide16)){
						$c = new stdClass();
						$c->operator = $params->condition_operator16;
						$c->to = $params->condition_field16;
						$c->custom_value = $params->condition_custom16;
						$c->action = ($params->condition_action16=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide16;
						$c->two_ways = $params->condition_twoways16;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups16)){
						$c = new stdClass();
						$c->operator = $params->condition_operator16;
						$c->to = $params->condition_field16;
						$c->custom_value = $params->condition_custom16;
						$c->action = ($params->condition_ugaction16=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups16;
						$c->two_ways = $params->condition_twoways16;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 17
				if($params->condition_operator17 > 0){
					if(!empty($params->condition_hide17)){
						$c = new stdClass();
						$c->operator = $params->condition_operator17;
						$c->to = $params->condition_field17;
						$c->custom_value = $params->condition_custom17;
						$c->action = ($params->condition_action17=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide17;
						$c->two_ways = $params->condition_twoways17;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups17)){
						$c = new stdClass();
						$c->operator = $params->condition_operator17;
						$c->to = $params->condition_field17;
						$c->custom_value = $params->condition_custom17;
						$c->action = ($params->condition_ugaction17=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups17;
						$c->two_ways = $params->condition_twoways17;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 18
				if($params->condition_operator18 > 0){
					if(!empty($params->condition_hide18)){
						$c = new stdClass();
						$c->operator = $params->condition_operator18;
						$c->to = $params->condition_field18;
						$c->custom_value = $params->condition_custom18;
						$c->action = ($params->condition_action18=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide18;
						$c->two_ways = $params->condition_twoways18;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups18)){
						$c = new stdClass();
						$c->operator = $params->condition_operator18;
						$c->to = $params->condition_field18;
						$c->custom_value = $params->condition_custom18;
						$c->action = ($params->condition_ugaction18=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups18;
						$c->two_ways = $params->condition_twoways18;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				// Condition 19
				if($params->condition_operator19 > 0){
					if(!empty($params->condition_hide19)){
						$c = new stdClass();
						$c->operator = $params->condition_operator19;
						$c->to = $params->condition_field19;
						$c->custom_value = $params->condition_custom19;
						$c->action = ($params->condition_action19=='hide' ? 'fields_hide' : 'fields_show');
						$c->fields_target = $params->condition_hide19;
						$c->two_ways = $params->condition_twoways19;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
					if(!empty($params->condition_usergroups19)){
						$c = new stdClass();
						$c->operator = $params->condition_operator19;
						$c->to = $params->condition_field19;
						$c->custom_value = $params->condition_custom19;
						$c->action = ($params->condition_ugaction19=='add' ? 'usergroups_add' : 'usergroups_remove');
						$c->usergroups_target = $params->condition_usergroups19;
						$c->two_ways = $params->condition_twoways19;
						if(empty($c->to)) $c->to = '';
						if(empty($c->custom_value)) $c->custom_value = '';
						if(empty($c->fields_target)) $c->fields_target = '';
						if(empty($c->two_ways)) $c->two_ways = 1;
						$conditions[]=$c;
					}
				}
				
				if(count($conditions)){
					$conditions = json_encode($conditions);
					$query ="UPDATE `#__jsn_fields` SET `conditions` = ".$db->quote($conditions)." WHERE id=".$field->id;
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
		catch (Exception $e) { }

		// Change Collation
		try {
			$query ="ALTER TABLE #__jsn_users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
			$db->setQuery($query);
			$db->execute();
			$query ="ALTER TABLE #__jsn_fields CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }

		// Joomla updater
		/*$query=$db->getQuery(true);
		$query->select('location')->from('#__update_sites')->where('`name`="Easy Profile"');
		$db->setQuery($query);
		$updaterSiteId=$db->loadResult();

		if($updaterSiteId==false)
		{
			$query='INSERT INTO #__update_sites(`name`,`type`,`location`,`enabled`) VALUES ("Easy Profile","extension","https://www.easy-profile.com/extension.xml",1)';
			$db->setQuery($query);
			$db->execute();
			$updaterSiteId = $db->insertid();
		}

		$query=$db->getQuery(true);
		$query->select('extension_id')->from('#__extensions')->where('`name`="com_jsn"');
		$db->setQuery($query);
		$updaterExtensionId= $db->loadResult();

		try {
			$query='INSERT INTO #__update_sites_extensions(`update_site_id`,`extension_id`) VALUES ('.$updaterSiteId.','.$updaterExtensionId.')';
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e) { }*/
		
		// Plugin enable
		$query=$db->getQuery(true);
		$query->update('#__extensions')->set($db->quoteName('enabled').'=1')
			->where($db->quoteName('element').' IN ('.
			$db->quote('jsn_author').','.
			$db->quote('jsn_auth').','.
			$db->quote('jsn_system').','.
			$db->quote('jsn_content').','.
			$db->quote('jsn_users').','.
			$db->quote('jsn_privacy').','.
			$db->quote('usergroups').','.
			$db->quote('ajaxuserlist').','.
			$db->quote('socialconnect').')');
		$db->setQuery($query);

		$result = $db->query();

		$query->update('#__extensions')->set($db->quoteName('ordering').'=0')
			->where($db->quoteName('element').' IN ('.
			$db->quote('jsn_system').')');
		$db->setQuery($query);
		
		$result = $db->query();

		$lang = JFactory::getLanguage();
		$lang->load('com_jsn');

		$message='<div style="text-align:center"><img style="width:200px;" src="http://www.easy-profile.com/easyprofile.png" /><br /><br /><b>';
		
		if($type=='install') $message.='Successfully installed';
		else $message.='Successfully updated';

		$message.='</b><br /><br /><a class="btn btn-warning" target="_blank" href="https://www.easy-profile.com/support"><i class="icon icon-question-sign"></i> <b>Support</b></a> <a class="btn btn-info" target="_blank" href="http://docs.easy-profile.com/"><i class="icon icon-book"></i> <b>Docs</b></a><br /><br /></div>';
		
		JFactory::getApplication()->enqueueMessage($message);

		//$app->redirect('index.php?option=com_jsn&task='.$type);
	}
}
?>
