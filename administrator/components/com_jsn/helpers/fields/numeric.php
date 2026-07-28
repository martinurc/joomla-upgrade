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
$_FIELDTYPES['numeric']='COM_JSN_FIELDTYPE_NUMERIC';

class JsnNumericFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." INTEGER";
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
		$defaultvalue=($item->params->get('numeric_defaultvalue','')!='' ? 'default="'.$item->params->get('numeric_defaultvalue','').'"' : '');

		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isClient('site')) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isClient('site')) $readonly='readonly="true"';
		else $readonly='';
		
		$max='max="'.$item->params->get('numeric_max',999999).'"';
		$min='min="'.$item->params->get('numeric_min',0).'"';
		$step='step="'.$item->params->get('numeric_step',1).'"';

		if(isset($_SERVER['HTTP_USER_AGENT'])) $agent = $_SERVER['HTTP_USER_AGENT']; else $agent='';
		if(strlen(strstr($agent,"Firefox")) > 0 ) $type='text';
		else $type='numeric';
		
		$xml='';
		
		$xml.='
			
			<field
				name="'.$item->alias.'"
				type="'.$type.'"
				id="'.$item->alias.'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				size="30"
				'.$defaultvalue.'
				'.$max.'
				'.$min.'
				'.$step.'
				'.$readonly.'
				class="'.$item->params->get('field_cssclass','').'"
				required="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				validate="numeric"
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
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isClient('site')) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isClient('site')) return;
		$alias=$field->alias;
		if(isset($data[$alias])) $storeData[$alias]=$data[$alias];
	}
	
	public static function getSearchInput($field)
	{
		$max='max="'.$field->params->get('numeric_max',999999).'" ';
		$min='min="'.$field->params->get('numeric_min',0).'" ';
		$step='step="'.$field->params->get('numeric_step',1).'" ';
		
		$return=array();
		$return[]='<input id="jform_'.str_replace('-','_',$field->alias).'" type="hidden" name="'.$field->alias.'" value="1" /><div class=""><div class="numericsearch">';		
		$return[]='<input type="number" placeholder="'.JText::_('COM_JSN_STARTNUMERIC').'" '.$min.$max.$step.'name="'.$field->alias.'_from" value="'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').'"/>';
		$return[]='</div> <div class="numericsearch">';
		$return[]='<input type="number" placeholder="'.JText::_('COM_JSN_ENDNUMERIC').'" '.$min.$max.$step.'name="'.$field->alias.'_to" value="'.JFactory::getApplication()->input->get($field->alias.'_to','','raw').'"/>';
		$return[]='</div><div class="numericsearchtip"><span class="label label-default">'.JText::_('COM_JSN_CHOOSENUMERICINTERVAL').'</span></div></div>';
		
		return implode('',$return);
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$db=JFactory::getDbo();
		if(JFactory::getApplication()->input->get($field->alias.'_from','','raw')!='') $query->where('b.'.$db->quoteName($field->alias).' >= '.$db->quote(JFactory::getApplication()->input->get($field->alias.'_from','','raw')));
		if(JFactory::getApplication()->input->get($field->alias.'_to','','raw')!='') $query->where('b.'.$db->quoteName($field->alias).' <= '.$db->quote(JFactory::getApplication()->input->get($field->alias.'_to','','raw')));
	}
		

}
