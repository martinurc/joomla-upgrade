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
$_FIELDTYPES['filetype']='COM_JSN_FIELDTYPE_FILETYPE';

class JsnFiletypeFieldHelper
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
		$defaultvalue='';//($item->params->get('image_defaultvalue','')!='' ? 'default="'.JsnHelper::xmlentities($item->params->get('image_defaultvalue','')).'"' : '');//(isset($item->params['image_defaultvalue']) && $item->params['image_defaultvalue']!='' ? 'default="'.$item->params['image_defaultvalue'].'"' : '');
		
		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isClient('site')) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isClient('site')) $readonly='readonly="true"';
		else $readonly='';
		
		$xml='
			<field
				name="'.$item->alias.'"
				type="filetype"
				downloadtext="'.($item->params->get('filetype_label','')=='' ? 'Download' : JsnHelper::xmlentities($item->params->get('filetype_label','')) ).'"
				id="'.$item->alias.'"
				class="'.$item->alias.' '.$item->params->get('field_cssclass','').'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				required="'.($item->required && JFactory::getApplication()->input->get('jform',null,'array')==null ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				requiredfile="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				validate="filetype"
				'.$readonly.'
				mime="'.$item->params->get('filetype_ext','pdf|zip|doc|docx').'"
			/>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		if(isset($user->$alias) && $user->$alias!='' && file_exists(JPATH_SITE.'/'.$user->$alias))
		{
			$data->$alias=$user->$alias;
		}
	}
	
	public static function storeData($field, $data, &$storeData)
	{	
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isClient('site')) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isClient('site')) return;
		$upload_path=$field->params->get('filetype_path','images/profiler/');
		
		// Set Upload Dir
		$upload_dir=JPATH_SITE.'/'.$upload_path;
		if(!file_exists($upload_dir)) 
		{ 
			mkdir($upload_dir); 
		}

		// Get Alias
		$alias=$field->alias;
		if(isset($data[$alias])) $storeData[$alias]=$data[$alias];
		
		// Delete Image
		$jform=JFactory::getApplication()->input->post->getArray();
		if(isset($jform['jform'][$field->alias.'_delete']))
		{
			// Delete old file
			foreach (glob($upload_dir.$alias.$data['id'].'*') as $deletefile)
			{
				unlink($deletefile);
			}
			
			$storeData[$alias]='';
			return;
		}
		
		
		$jform=JFactory::getApplication()->input->files->get('jform',null,'raw');
		if(isset($jform['upload_'.$alias])) $jform_file=$jform['upload_'.$alias];
		if(isset($jform_file['name']) && strlen($jform_file['name'])>4)
		{
			foreach (glob($upload_dir.$alias.$data['id'].'*') as $deletefile)
			{
				unlink($deletefile);
			}
			$md5=md5(time().rand());
			$filename=$alias.$data['id'].'_'.$md5;
			$name=$jform_file['name'];
			$ext = strtolower($name[strlen($name)-4].$name[strlen($name)-3].$name[strlen($name)-2].$name[strlen($name)-1]);
			if ($ext[0] == '.') $ext = substr($ext, 1, 3);
			move_uploaded_file($jform_file['tmp_name'], $upload_dir.'/'.$filename.'.'.$ext);
			$storeData[$alias]=$upload_path.$filename.'.'.$ext;
		}
		
	}

	public static function getSearchInput($field)
	{
		$return='<fieldset class="checkboxes" id="jform_'.str_replace('-','_',$field->alias).'">
					<label class="checkbox inline"><input type="checkbox" name="'.$field->alias.'" value="1"'.(JFactory::getApplication()->input->get($field->alias,'','raw')!='' ? "checked=checked" : '').' /><b>'.JText::_('JYES').'</b></label>
				</fieldset>';
		return $return;
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$db=JFactory::getDbo();
		$query->where('b.'.$db->quoteName($field->alias).' LIKE '.$db->quote('_%'));
	}
	
	public static function filetype($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			return $field->getFile();
		}
		
	}

	public static function deleteUser($field,$user){
		$upload_path=$field->params->get('filetype_path','images/profiler/');
		
		// Set Upload Dir
		$upload_dir=JPATH_SITE.'/'.$upload_path;
		
		$userId = \Joomla\Utilities\ArrayHelper::getValue($user, 'id', 0, 'int');
		
		if($userId > 0) foreach (glob($upload_dir.$field->alias.$userId.'*') as $deletefile)
		{
				unlink($deletefile);
		}
	}
	

}
