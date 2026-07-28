<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnCoreFieldHelper
{
	public static function create($alias)
	{
		
	}
	
	public static function delete($alias)
	{
		
	}
	
	public static function getXml($item = null)
	{
		$config = JComponentHelper::getParams('com_jsn');
		$xml='';
		if(JFactory::getApplication()->isClient('administrator'))
		{
			$xml='
				<field name="name" type="hidden"
					class="inputbox"
					default="formatName"
				/>';
			if($config->get('facebook_enabled',0) || $config->get('twitter_enabled',0) || $config->get('google_enabled',0) || $config->get('linkedin_enabled',0) || $config->get('instagram_enabled',0)) {
				$xml.='<field name="spconnect" label="&lt;h4&gt;SocialConnect&lt;/h4&gt;" type="spacer" />';
				if($config->get('facebook_enabled',0)) $xml.='<field name="facebook_id" type="text"
					class="inputbox"
					default=""
					label="Facebook ID"
				/>';
				if($config->get('twitter_enabled',0)) $xml.='<field name="twitter_id" type="text"
					class="inputbox"
					default=""
					label="Twitter ID"
				/>';
				if($config->get('google_enabled',0)) $xml.='<field name="google_id" type="text"
					class="inputbox"
					default=""
					label="Google Plus ID"
				/>';
				if($config->get('linkedin_enabled',0)) $xml.='<field name="linkedin_id" type="text"
					class="inputbox"
					default=""
					label="LinkedIn ID"
				/>';
				if($config->get('instagram_enabled',0)) $xml.='<field name="instagram_id" type="text"
					class="inputbox"
					default=""
					label="Instagram ID"
				/>';
			}
		}
		elseif(JFactory::getApplication()->input->get('view')=='registration')
		{
			$xml='
				<field name="name" type="hidden"
					class="inputbox"
					default="formatName"
				/>
			';
		}
		else
		{
			$xml='
				<field name="name" type="hidden"
					class="inputbox"
				/>
				<field name="id" type="hidden"
					filter="integer"
				/>
			';
		}
		return $xml;
	}
	

}
