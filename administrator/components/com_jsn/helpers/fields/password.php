<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnPasswordFieldHelper
{
	public static function create($alias)
	{
		
	}
	
	public static function delete($alias)
	{
		
	}
	
	public static function getXml($item)
	{
		$version=new JVersion();
		$xml='';
		if(JFactory::getApplication()->isClient('site'))
		{
			$configUsers=JComponentHelper::getParams('com_users');
			$configJsn=JComponentHelper::getParams('com_jsn');
			if(JFactory::getApplication()->input->get('view')=='registration') $required='required="true"';
			else $required='';
			if($configJsn->get('passwordstrengthmeter',0)) $strengthmeter='strengthmeter="true"';
			else $strengthmeter='';
			$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
			if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
			$placeholder=($item->params->get('password_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('password_placeholder','')).'"' : '');
			$placeholder2=($item->params->get('password_placeholder2','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('password_placeholder2','')).'"' : '');
			$xml.='
				<field name="password1" type="passwordfull"
					autocomplete="new-password"
					class="validate-password '.$item->params->get('field_cssclass','').'"
					description="COM_USERS_DESIRED_PASSWORD"
					filter="raw"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					size="30"
					minimum_length="'.$configUsers->get('minimum_length',4).'"
					minimum_integers="'.$configUsers->get('minimum_integers',0).'"
					minimum_symbols="'.$configUsers->get('minimum_symbols',0).'"
					minimum_uppercase="'.$configUsers->get('minimum_uppercase',0).'"
					'.(((!empty($version->RELEASE) && $version->RELEASE=='3.0') || $version->getShortVersion()=='3.1.1' || $version->getShortVersion()=='3.1.0') ? 'field="password2" validate="equals" message="COM_USERS_PROFILE_PASSWORD1_MESSAGE"' : 'validate="password"').'
					'.$required.'
					'.$placeholder.'
					'.$strengthmeter.'
				/>
				';
			$config = JComponentHelper::getParams('com_jsn');
			if($config->get('confirmuserpassword',1) || JFactory::getApplication()->input->get('layout','')=='edit')
			{
				$xml.='
				<field name="password2" type="confirmpassword"
					autocomplete="new-password"
					'.(((!empty($version->RELEASE) && $version->RELEASE=='3.0') || $version->getShortVersion()=='3.1.1' || $version->getShortVersion()=='3.1.0') ? '' : 'field="password1" validate="equals" message="COM_USERS_PROFILE_PASSWORD1_MESSAGE"').'
					class="validate-confirmpassword"
					description="COM_USERS_PROFILE_PASSWORD2_DESC"
					filter="raw"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_('COM_JSN_PROFILE_PASSWORD2_LABEL').'</span>') : 'COM_JSN_PROFILE_PASSWORD2_LABEL').'"
					size="30"
					'.$required.'
					'.$placeholder2.'
				/>
				';
			}
		}
		else{
			$configJsn=JComponentHelper::getParams('com_jsn');
			if($configJsn->get('passwordstrengthmeter',0)) $strengthmeter='strengthmeter="true"';
			
			$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
			if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
			$placeholder=($item->params->get('password_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('password_placeholder','')).'"' : '');
			$placeholder2=($item->params->get('password_placeholder2','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('password_placeholder2','')).'"' : '');
			if(JFactory::getApplication()->input->get('option','')=='com_admin')
			{
				$password1_desc='COM_ADMIN_USER_FIELD_PASSWORD_DESC';
				$password2_label='COM_ADMIN_USER_FIELD_PASSWORD2_LABEL';
				$password2_desc='COM_ADMIN_USER_FIELD_PASSWORD2_DESC';
			}
			else {
				$password1_desc='COM_USERS_USER_FIELD_PASSWORD_DESC';
				$password2_label='COM_USERS_USER_FIELD_PASSWORD2_LABEL';
				$password2_desc='COM_USERS_USER_FIELD_PASSWORD2_DESC';
			}
			
			$xml.='
				<field name="password" type="passwordfull"
					autocomplete="new-password"
					class="validate-password"
					description="'.$password1_desc.'"
					filter="raw"
					validate="password"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					size="30"
					'.$placeholder.'
				/>
				
				<field name="password2" type="confirmpassword"
					autocomplete="new-password"
					class="validate-confirmpassword"
					description="'.$password2_desc.'"
					filter="raw"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($password2_label).'</span>') : $password2_label).'"
					size="30"
					validate="equals"
					field="password"
					'.$placeholder2.'
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

}
