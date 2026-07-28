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
$_FIELDTYPES['checkboxlist']='COM_JSN_FIELDTYPE_CHECKBOX';

class JsnCheckboxlistFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." VARCHAR(255)";
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
		$defaultvalue=($item->params->get('checkbox_defaultvalue','')!='' ? 'default="'.JsnHelper::xmlentities($item->params->get('checkbox_defaultvalue','')).'"' : '');//(isset($item->params['text_defaultvalue']) && $item->params['text_defaultvalue']!='' ? 'default="'.$item->params['text_defaultvalue'].'"' : '');
		$inline=($item->params->get('checkbox_inline',0) ? 'optioninline="'.$item->params->get('checkbox_inline','').'"' : '');
		$dbtable=($item->params->get('checkbox_dbopttable','')=='' ? '' : 'dbopttable="'.$item->params->get('checkbox_dbopttable','').'"');
		$dbvalue=($item->params->get('checkbox_dboptvalue','')=='' ? '' : 'dboptvalue="'.$item->params->get('checkbox_dboptvalue','').'"');
		$dbtext=($item->params->get('checkbox_dbopttext','')=='' ? '' : 'dbopttext="'.$item->params->get('checkbox_dbopttext','').'"');
		$dbwhere=($item->params->get('checkbox_dboptwhere','')=='' ? '' : 'dboptwhere="'.JsnHelper::xmlentities($item->params->get('checkbox_dboptwhere','')).'"');

		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isClient('site')) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isClient('site')) $readonly='readonly="true"';
		else $readonly='';
		
		$options=array();
		$optTxt=explode("\n",$item->params->get('checkbox_options',''));
		foreach($optTxt as $opt)
		{
			if(strlen($opt)){
				$opt=explode('|',$opt);
				if(count($opt)==1) $options[]='<option value="'.trim(JsnHelper::xmlentities($opt[0])).'">'.trim(JsnHelper::xmlentities($opt[0])).'</option>';
				if(count($opt)==2) $options[]='<option value="'.trim(JsnHelper::xmlentities($opt[0])).'">'.trim(JsnHelper::xmlentities($opt[1])).'</option>';
			}
		}
		
		$xml='';
		
		$xml.='
			
			<field
				name="'.$item->alias.'"
				type="checkboxlist"
				id="'.$item->alias.'"
				inline="inline"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				'.$defaultvalue.'
				'.$inline.'
				'.$dbtable.'
				'.$dbvalue.'
				'.$dbtext.'
				'.$dbwhere.'
				'.$readonly.'
				class="'.$item->params->get('field_cssclass','').'"
				required="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
			>
			'.implode(' ',$options).'
			</field>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		if(isset($user->$alias)) $data->$alias=json_decode($user->$alias);
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isClient('site')) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isClient('site')) return;
		$alias=$field->alias;
		if(isset($data[$alias])) $storeData[$alias]=json_encode($data[$alias]);
		else $storeData[$alias]='';
		if($field->params->get('field_readonly','')=="1" && JFactory::getApplication()->isClient('site') && isset($storeData[$alias])) {unset($storeData[$alias]);}
		if($field->params->get('field_readonly','')=="2" && JFactory::getApplication()->input->get->get('task','')=='profile.save' && JFactory::getApplication()->isClient('site') && isset($storeData[$alias])) {unset($storeData[$alias]);}
	}
	
	public static function checkboxlist($field)
	{
		$value=$field->__get('value');
		if (empty($value) && (string) $value != '0')
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			$options=$field->getOptions();
			$result=array();
			foreach($options as $option)
			{
				if(in_array($option->value,$value)) $result[]=JText::_($option->text);
			}
			return implode(', ',$result);
		}
	}
	
	public static function getSearchInput($field)
	{
		$selectedOptions=JFactory::getApplication()->input->get($field->alias,null,'raw');
		$options=array();
		$optTxt=explode("\n",$field->params->get('checkbox_options',''));
		foreach($optTxt as $opt)
		{
			if(strlen($opt)){
				$opt=explode('|',$opt);
				if(count($opt)==1) $options[]=JHtml::_('select.option', trim($opt[0]), JText::_(trim(htmlspecialchars($opt[0]))));
				if(count($opt)==2) $options[]=JHtml::_('select.option', trim($opt[0]), JText::_(trim(htmlspecialchars($opt[1]))));
			}
		}

		$dbOptTable=$field->params->get('checkbox_dbopttable','');
		$dbOptValue=$field->params->get('checkbox_dboptvalue','');
		$dbOptText=$field->params->get('checkbox_dbopttext','');
		$dbOptWhere=$field->params->get('checkbox_dboptwhere','');
		if(!empty($dbOptTable) && !empty($dbOptValue) && !empty($dbOptText)) // set the alias of your field (width this conditions the select type work normally for all field except for this field)
		{	
			$db		= JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($dbOptValue.' AS value, '.$dbOptText.' AS text')->from($dbOptTable);
			
			if(!empty($dbOptWhere)) $query->where($dbOptWhere);
			
			$query->order('text');
			
			$db->setQuery($query);

			try
			{
				$dbOptions = $db->loadObjectList();
				foreach($dbOptions as $option)
				{
					$options[]=JHtml::_('select.option', trim($option->value), JText::_(trim(htmlspecialchars($option->text))));
				}
			}
			catch (RuntimeException $e)
			{
			}
			
			
		}
		
		$return=JHtml::_('select.radiolist', $options, $field->alias.'[]', null, 'value', 'text', null, $field->alias);
		$from=array('<div','</div>','type="radio"','class="radio"','class="controls"');
		if($field->params->get('checkbox_inline',0)==0) $to=array('<fieldset class="checkboxes"','</fieldset>','type="checkbox"','class="checkbox"','id="jform_'.str_replace('-','_',$field->alias).'"');
		else  $to=array('<fieldset class="checkboxes"','</fieldset>','type="checkbox"','class="checkbox inline"','id="jform_'.str_replace('-','_',$field->alias).'"');
		if($selectedOptions!=null)
		{
			foreach($selectedOptions as $selectedOption)
			{
				$from[]='value="'.$selectedOption.'"';
				$to[]='value="'.$selectedOption.'" checked="checked"';
			}
		}
		return str_replace($from,$to,$return);
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$options=JFactory::getApplication()->input->get($field->alias,null,'raw');
		$db=JFactory::getDbo();
		
		$where='';
		foreach($options as $option)
		{
			$where.='b.'.$db->quoteName($field->alias).' LIKE '.$db->quote('%"'.$option.'"%').' OR ';
		}
		$where=substr($where, 0,-4);
		$query->where('('.$where.')');
	}
		

}
