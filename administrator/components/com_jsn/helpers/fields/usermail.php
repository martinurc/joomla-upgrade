<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnUsermailFieldHelper
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
			$placeholder=($item->params->get('usermail_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('usermail_placeholder','')).'"' : '');
			$placeholder2=($item->params->get('usermail_placeholder2','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('usermail_placeholder2','')).'"' : '');

			if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
			elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
			else $readonly='';

			$xml.='
				<field name="email1" type="emailfull"
					description="COM_USERS_PROFILE_EMAIL1_DESC"
					filter="string"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					message="COM_USERS_PROFILE_EMAIL1_MESSAGE"
					required="true"
					size="30"
					unique="true"
					validate="email"
					'.$placeholder.'
					'.$readonly.'
					class="'.$item->params->get('field_cssclass','').'"
				/>

				
			';
			$config = JComponentHelper::getParams('com_jsn');
			if($config->get('confirmusermail',0))
			{
				$xml.='
					<field name="email2" type="confirmemail"
						description="COM_USERS_PROFILE_EMAIL2_DESC"
						field="email1"
						class="validate-confirmemail '.$item->params->get('field_cssclass','').'"
						filter="string"
						label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_('COM_USERS_PROFILE_EMAIL2_LABEL').'</span>') : 'COM_USERS_PROFILE_EMAIL2_LABEL').'"
						message="COM_USERS_PROFILE_EMAIL2_MESSAGE"
						required="true"
						size="30"
						validate="equals"
						'.$placeholder2.'
						'.$readonly.'
					/>
				';
			}
		}
		else{
			$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
			$placeholder=($item->params->get('usermail_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('usermail_placeholder','')).'"' : '');
			$xml.='
				<field name="email" type="emailfull"
					class="inputbox '.$item->params->get('field_cssclass','').'"
					description="COM_USERS_USER_FIELD_EMAIL_DESC"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					required="true"
					size="30"
					validate="email"
					'.$placeholder.'
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
		$query->where('a.'.$db->quoteName('email').' LIKE '.$db->quote('%'.JFactory::getApplication()->input->get('email',null,'raw').'%'));
	}
	
	public static function usermail($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			JPluginHelper::importPlugin('content');
			return JHtml::_('content.prepare', '<a href="mailto:'.$value.'">'.$value.'</a>', '', 'jsn_content.content');
		}
	}

}
