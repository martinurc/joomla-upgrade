<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

//if(!$this->params->def('export', 0)) die('Not authorized');
require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
require_once(JPATH_SITE.'/components/com_jsn/helpers/parsecsv.lib.php');

$csv = new parseCSV();

$csv->output_delimiter=';';

$csv->data=array();

$db = JFactory::getDbo();

$config = JComponentHelper::getParams('com_jsn');
if($config->get('export_all_fields',1)){
	
	$query = $db->getQuery(true);
	$query->select('alias')->from('#__jsn_fields')->where("type NOT LIKE 'delimeter' AND level = 2 AND published = 1 AND alias NOT IN ('registerdate','lastvisitdate')");
	$jsn_fields = $db->setQuery($query)->loadColumn();

	$jsn_fields[] = 'instagram_id';
	$jsn_fields[] = 'facebook_id';
	$jsn_fields[] = 'twitter_id';
	$jsn_fields[] = 'google_id';
	$jsn_fields[] = 'linkedin_id';

	$joomla_fields = array('id','name','username','email','password','block','sendEmail','registerDate','lastvisitDate','activation','params','lastResetTime','resetCount','otpKey','otep','requireReset','groups');
	$export_fields = array_unique(array_merge($joomla_fields,$jsn_fields));
}
else{
	$jsn_fields = $config->get('export_list_fields',array());
	$joomla_fields = array();
	if(in_array('_system',$config->get('export_list_fields',array()))) $joomla_fields = array('id','block','sendEmail','activation','params','lastResetTime','resetCount','otpKey','otep','requireReset');
	$export_fields = array_unique(array_merge($joomla_fields,$jsn_fields));
}

$where=array();

foreach($export_fields as $field){
	$where[]=$db->quote($field);
}

$query=$db->getQuery(true);
$query->select('type,alias,id')->from('#__jsn_fields')->where('alias IN('.implode(',',$where).')');
$db->setQuery($query);
$fields_map=$db->loadAssocList();

$readable_field_types = array('checkboxlist','selectlist','radiolist');
$readable_fields = array();
$readable_datefields = array('registerDate','lastvisitDate','registerdate','lastvisitdate');
foreach($fields_map as $field_map){
	if($field_map['type']=='gmap')
	{
		$export_fields[]=$field_map['alias'].'_lat';
		$export_fields[]=$field_map['alias'].'_lng';
	}
	if(in_array($field_map['type'], $readable_field_types)) $readable_fields[]=$field_map['alias'];
}

$query=$db->getQuery(true);
$query->select('id,title')->from('#__usergroups');
$db->setQuery($query);
$jgroups_map=$db->loadAssocList('id');

$csv->titles=$export_fields;

$csv->output_delimiter=$config->get('export_separator', ';');

global $JSNLIST_DISPLAYED_ID;
if(is_array($this->items)) foreach($this->items as $item)
{
	$JSNLIST_DISPLAYED_ID=$item->id;
	$this->user = JsnHelper::getUser($item->id);
	$jgroups=$this->user->getAuthorisedGroups();
	$this->user->groups=array();
	foreach ($jgroups as $group_id) {
		$this->user->groups[]=$jgroups_map[$group_id]['title'];
	}
	$row=array();
	foreach($export_fields as $field){
		
		if(in_array($field, $readable_fields) && JFactory::getApplication()->input->get('readable',0)) {
			$val = $this->user->getField($field);
		}
		elseif(in_array($field, $readable_datefields) && JFactory::getApplication()->input->get('readable',0)) {
			$val = $this->user->getValue($field);
			if($val=='0000-00-00 00:00:00') $val=JText::_('JNEVER');
			else $val = JHtml::_('date', $val,'Y-m-d H:i:s');
		}
		else {
			$val = $this->user->getValue($field);
		}
		if(is_array($val)) $val=implode(',',$val);
		//$row[]= '"'.str_replace('"', '""', $val).'"';
		$row[$field]=$val;
	}
	$csv->data[]=$row;
}

$JSNLIST_DISPLAYED_ID=false;

$csv->output('export_'.date('d-m-Y_H:i').'.csv');
JFactory::getApplication()->close();
