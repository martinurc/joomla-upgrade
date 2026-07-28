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
$_FIELDTYPES['textarea']='COM_JSN_FIELDTYPE_TEXTAREA';

class JsnTextareaFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." TEXT";
		$db->setQuery($query);
		$db->query();
	}
	
	public static function delete($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users DROP COLUMN ".$db->quoteName($alias);
		$db->setQuery($query);
		$db->query();
	}
	
	public static function getXml($item)
	{
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		$defaultvalue=($item->params->get('textarea_defaultvalue','')!='' ? 'default="'.JsnHelper::xmlentities($item->params->get('textarea_defaultvalue','')).'"' : '');
		$maxlength=($item->params->get('textarea_maxlength','')!='' ? 'maxlength="'.$item->params->get('textarea_maxlength','').'"' : '');
		$type=$item->params->get('textarea_type','textarea');
		$placeholder=($item->params->get('textarea_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('textarea_placeholder','')).'"' : '');
		if($type=='textarea') $filter='filter="raw"'; 
		else $filter='filter="raw"';

		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		else $readonly='';
		
		if(JFactory::getApplication()->input->get('option')=='com_jsn') $type='textarea';
		
		$xml='';
		
		$xml.='
			
			<field
				name="'.$item->alias.'"
				type="'.$type.'"
				id="'.$item->alias.'"
				origintype="'.$item->params->get('textarea_type','textarea').'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				'.$filter.'
				'.$defaultvalue.'
				'.$maxlength.'
				'.$placeholder.'
				'.$readonly.'
				class="'.$item->params->get('field_cssclass','').'"
				required="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
			/>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		if(isset($user->$alias)) $data->$alias=$user->$alias;
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isSite()) return;	
		$alias=$field->alias;
		if(isset($data[$alias])) $storeData[$alias]=$data[$alias];
	}
	
	public static function getSearchInput($field)
	{
		$return='<input id="jform_'.str_replace('-','_',$field->alias).'" type="text" placeholder="'.JText::_('COM_JSN_SEARCHFOR').' '.JText::_($field->title).'..." name="'.$field->alias.'" value="'.JFactory::getApplication()->input->get($field->alias,'','raw').'"/>';
		return $return;
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$db=JFactory::getDbo();
		$query->where('b.'.$db->quoteName($field->alias).' LIKE '.$db->quote('%'.JFactory::getApplication()->input->get($field->alias,null,'raw').'%'));
	}
	
	public static function textarea($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			//if(JText::_(strip_tags($value)) != strip_tags($value)) $value=JText::_(strip_tags($value));
			if(substr_count($value,'<p>')==1 && substr_count($value,' ')==0) $text=JText::_(strip_tags($value));
			if(method_exists($field,'getAttribute') && $field->getAttribute('origintype')=='textarea') return str_replace("\n","<br />",htmlspecialchars($value));
			else return $value;
		}
	}
		

}
