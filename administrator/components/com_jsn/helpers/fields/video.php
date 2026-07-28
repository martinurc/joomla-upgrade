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
$_FIELDTYPES['video']='COM_JSN_FIELDTYPE_VIDEO';

class JsnVideoFieldHelper
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
		$defaultvalue=($item->params->get('video_defaultvalue','')!='' ? 'default="'.JsnHelper::xmlentities($item->params->get('video_defaultvalue','')).'"' : '');
		$maxlength=($item->params->get('video_maxlength','')!='' ? 'maxlength="'.$item->params->get('video_maxlength','').'"' : '');
		$placeholder=($item->params->get('video_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('video_placeholder','')).'"' : '');

		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		else $readonly='';

		$type='video';
		
		$xml='';
		
		$xml.='
			
			<field
				name="'.$item->alias.'"
				type="'.$type.'"
				id="'.$item->alias.'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				validate="video"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				size="30"
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
		if(isset($user->$alias) && $data->$alias=='http://') $data->$alias='';
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isSite()) return;
		$alias=$field->alias;
		if(isset($data[$alias])){
			if(substr(trim($data[$alias]), 0, 4)=='http') $storeData[$alias]=$data[$alias];
			else $storeData[$alias]='http://'.$data[$alias];
		}
		if(isset($data[$alias]) && $storeData[$alias]=='http://') $storeData[$alias]='';
	}
	
	public static function video($field)
	{	
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			if(strpos(' '.$value,'vimeo')>1)
			{
				$value=substr($value,strrpos($value,'/')+1);
				return '<iframe src="//player.vimeo.com/video/'.$value.'?portrait=0&amp;badge=0" style="width:100%;height:300px;" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
			}
			$value=substr($value,strrpos($value,'/')+1);
			if(strrpos($value,'?v=')) $value=substr($value,strrpos($value,'?v=')+3);
			return '<iframe style="width:100%;height:300px;" src="//www.youtube.com/embed/'.$value.'" frameborder="0" allowfullscreen></iframe>';
		}
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
		

}
