<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/


defined('_JEXEC') or die;

JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_jsn/models', 'JsnModel');

abstract class ModJsnSearch
{
	public static function getJavascript(&$module,&$params)
	{
		$lang = JFactory::getLanguage();
		$lang->load('com_jsn');
		JHtml::_('jquery.framework');
		$db=JFactory::getDbo();
		$doc = JFactory::getDocument();
		$query = $db->getQuery(true);
		$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.published = 1')->order($db->escape('a.lft') . ' ASC');
		$db->setQuery( $query );
		$fields = $db->loadObjectList('alias');
		$script='jQuery(document).ready(function($){';
		foreach($fields as $field)
		{
			// Load Conditions
			if(empty($field->conditions)) $field->conditions = array();
			elseif(is_string($field->conditions)) $field->conditions=json_decode($field->conditions);
			
			// Conditions
			foreach($field->conditions as $condition)
			{
				if( !in_array($condition->action,array('fields_show','fields_hide') ) || empty($condition->fields_target) ) continue;
				$twoWays = $condition->two_ways;
				$actionShowFields = ($condition->action == 'fields_show' ? true : false);

				switch($condition->operator)
				{
					case 1:
						$operator='==';
						$operator_post='';
					break;
					case 2:
						$operator='>';
						$operator_post='';
					break;
					case 3:
						$operator='<';
						$operator_post='';
					break;
					case 4:
						$operator='.indexOf';
						$operator_post='!=-1';
					break;
					case 5:
						$operator='!=';
						$operator_post='';
					break;
					case 6:
						$operator='.indexOf';
						$operator_post='!=-1';
					break;
				}
				// Field to Show/Hide
				$fieldsTarget='';
				foreach($condition->fields_target as $field_target)
				{
					$fieldsTarget.='#jsearchform'.$module->id.'_'.str_replace('-','_',$field_target).',';
					$fieldsTarget.='#jsearchform'.$module->id.'_privacy_'.str_replace('-','_',$field_target).',';
				}
				$fieldsTarget=trim($fieldsTarget,',');
				// Field to Check
				if($condition->to=='_custom') $valueToCheck='var valToCheck="'.$condition->custom_value.'";';
				else $valueToCheck='
					var valToCheck=$("#jsearchform'.$module->id.'_'.str_replace('-','_',$condition->to).'").val();
					$("#jsearchform'.$module->id.'_'.str_replace('-','_',$condition->to).' input:checked").each(function(){
						if(valToCheck=="") valToCheck=$(this).val();
						else valToCheck=valToCheck+","+$(this).val();
					});
				';
				// Field to Bind
				if($condition->to=='_custom') $fieldToBind='#jsearchform'.$module->id.'_'.str_replace('-','_',$field->alias);
				else $fieldToBind='#jsearchform'.$module->id.'_'.str_replace('-','_',$field->alias).',#jsearchform'.$module->id.'_'.str_replace('-','_',$condition->to);
				// Show/Hide Script
				$script_showStart = 'if($(this).is(".norequired")){
								$(this).addClass("required").removeClass("norequired").attr("required","required").attr("aria-required","true");
								$(this).find("input[type!=\'checkbox\']").addClass("required").removeClass("norequired").attr("required","required").attr("aria-required","true");
							}
							$(this).closest(".control-group,.form-group").show().removeClass("hide");';
				$script_hideStart = 'if($(this).is(".required") || $(this).is("[aria-required=\'true\']") || $(this).is("[required=\'required\']")){
								$(this).addClass("norequired").removeClass("required").removeAttr("required").attr("aria-required","false");
								$(this).find("input[type!=\'checkbox\']").addClass("norequired").removeClass("required").removeAttr("required").attr("aria-required","false");
							}
							$(this).closest(".control-group,.form-group").hide().addClass("hide");';
				$script_showSlide = 'if($(this).is(".norequired")){
								$(this).addClass("required").removeClass("norequired").attr("required","required").attr("aria-required","true");
								$(this).find("input[type!=\'checkbox\']").addClass("required").removeClass("norequired").attr("required","required").attr("aria-required","true");
							}
							$(this).closest(".control-group,.form-group").slideDown().removeClass("hide");';
				$script_hideSlide = 'if($(this).is(".required") || $(this).is("[aria-required=\'true\']") || $(this).is("[required=\'required\']")){
								$(this).addClass("norequired").removeClass("required").removeAttr("required").attr("aria-required","false");
								$(this).find("input[type!=\'checkbox\']").addClass("norequired").removeClass("required").removeAttr("required").attr("aria-required","false");
							}
							$(this).closest(".control-group,.form-group").slideUp(function(){$(this).addClass("hide");});';
							
				// Code
				$scriptval='
					var val=$("#jsearchform'.$module->id.'_'.str_replace('-','_',$field->alias).'").val();
				';
				if($field->type=='radiolist')
					$scriptval='
						var val=$("#jsearchform'.$module->id.'_'.str_replace('-','_',$field->alias).' input:checked").val();
					';
				if($field->type=='checkboxlist')
					$scriptval='
						var val="";
						$("#jsearchform'.$module->id.'_'.str_replace('-','_',$field->alias).' input:checked").each(function(){
							if(val=="") val=$(this).val();
							else val=val+","+$(this).val();
						});
					';
				$script.='
					'.$scriptval.'
					if(val==null || val==undefined) val="";
					'.$valueToCheck.'
					if(valToCheck==null || valToCheck==undefined) valToCheck="";
					if($("#jsearchform'.$module->id.'_'.str_replace('-','_',$field->alias).'").length) if(val'.$operator.'(valToCheck)'.$operator_post.')
						$("'.$fieldsTarget.'").each(function(){
							'.($actionShowFields ? $script_showStart : $script_hideStart).'
						});
					';
					if($twoWays) $script.='else
						$("'.$fieldsTarget.'").each(function(){
							'.($actionShowFields ? $script_hideStart : $script_showStart).'
						});';
					$script.='
					$("'.$fieldToBind.'").bind("change keyup",function(){
						'.$scriptval.'
						if(val==null || val==undefined) val="";
						'.$valueToCheck.'
						if(valToCheck==null || valToCheck==undefined) valToCheck="";
						if(val'.$operator.'(valToCheck)'.$operator_post.')
							$("'.$fieldsTarget.'").each(function(){
								'.($actionShowFields ? $script_showSlide : $script_hideSlide).'
							});
						';
						if($twoWays) $script.='else
							$("'.$fieldsTarget.'").each(function(){
								'.($actionShowFields ? $script_hideSlide : $script_showSlide).'
							});';
					$script.='
					});';
				
			}
		}
		$script.='});';
		$doc->addScriptDeclaration( $script );
	}
}
