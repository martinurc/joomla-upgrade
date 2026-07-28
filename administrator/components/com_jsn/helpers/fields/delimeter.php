<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


global $_FIELDTYPES;
$_FIELDTYPES['delimeter']='COM_JSN_FIELDTYPE_DELIMETER';

class JsnDelimeterFieldHelper
{
	public static function create($alias)
	{
		
	}
	
	public static function delete($alias)
	{
		
	}
	
	public static function getXml($item)
	{
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		$xml='
			<field
				name="'.$item->alias.'"
				type="delimeter"
				id="'.$item->alias.'"
				text="'.JsnHelper::xmlentities($item->description).'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				class="'.$item->params->get('field_cssclass','').'"
			/>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		$data->$alias=$field->description;
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		
	}
	
	public static function delimeter($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			//if(JText::_(strip_tags($value)) != strip_tags($value)) $value=JText::_(strip_tags($value));
			if(substr_count($value,'<p>')==1 && substr_count($value,' ')==0) $value=JText::_(strip_tags($value));
			JPluginHelper::importPlugin('content');
			return JHtml::_('content.prepare', $value, '', 'jsn_content.content');
		}
	}


}
